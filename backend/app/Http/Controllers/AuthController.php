<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginOtp;
use App\Models\UserSession;
use App\Services\JWTService;
use App\Mail\SendOTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Generate Captcha (returns UUID key and base64 SVG image)
     */
    public function getCaptcha()
    {
        $code = (string) rand(10000, 99999);
        $key = (string) Str::uuid();

        // Cache the captcha code for 3 minutes
        Cache::put('captcha_' . $key, $code, now()->addMinutes(3));

        // Generate SVG Captcha image
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="42" viewBox="0 0 130 42">';
        $svg .= '<rect width="100%" height="100%" fill="#f8f9fa" rx="6" ry="6"/>';
        
        // Add random lines for noise
        for ($i = 0; $i < 6; $i++) {
            $x1 = rand(0, 130); $y1 = rand(0, 42);
            $x2 = rand(0, 130); $y2 = rand(0, 42);
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="#dee2e6" stroke-width="1.5"/>';
        }

        // Add code characters with random rotations
        $fontSize = 22;
        $x = 18;
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $y = rand(26, 32);
            $angle = rand(-15, 15);
            $svg .= '<text x="'.$x.'" y="'.$y.'" font-family="Courier New, monospace" font-weight="bold" font-size="'.$fontSize.'" fill="#0d6efd" transform="rotate('.$angle.', '.$x.', '.$y.')">'.$char.'</text>';
            $x += 20;
        }
        $svg .= '</svg>';

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return response()->json([
            'captcha_key' => $key,
            'captcha_image' => $base64Image,
        ]);
    }

    /**
     * Stage 1: Login (Credentials + Captcha) -> Sends OTP
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login_identifier' => 'required|string', // username, email, or phone
            'password' => 'required|string',
            'captcha_key' => 'required|string',
            'captcha_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify Captcha
        $cachedCode = Cache::pull('captcha_' . $request->captcha_key);
        if (!$cachedCode || strtolower($cachedCode) !== strtolower($request->captcha_code)) {
            return response()->json([
                'errors' => ['captcha_code' => ['Kode captcha tidak cocok atau sudah kedaluwarsa.']]
            ], 422);
        }

        // Search user by username, email, or cellphone
        $user = User::where(function($query) use ($request) {
            $query->where('username', $request->login_identifier)
                  ->orWhere('email', $request->login_identifier)
                  ->orWhere('cellphone', $request->login_identifier);
        })->first();

        // Check password and user existence
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'errors' => ['login_identifier' => ['Username, email, nomor HP, atau password salah.']]
            ], 401);
        }

        // Check user status
        if ($user->status !== 'active') {
            return response()->json([
                'errors' => ['login_identifier' => ['Akun Anda berstatus nonaktif. Silakan hubungi admin.']]
            ], 403);
        }

        // Determine destination email
        $email = $user->email;
        if (!$email && $user->employee) {
            $email = $user->employee->email;
        }

        if (!$email) {
            return response()->json([
                'errors' => ['login_identifier' => ['Akun Anda tidak memiliki alamat email untuk pengiriman OTP.']]
            ], 422);
        }

        // Generate 4-digit OTP
        $otp = (string) rand(1000, 9999);
        $otpHash = Hash::make($otp);

        // Store OTP in database
        LoginOtp::create([
            'user_id' => $user->id,
            'otp_hash' => $otpHash,
            'channel' => 'email',
            'sent_to' => $email,
            'expires_at' => now()->addMinutes(3),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Send OTP Email using Mailpit
        try {
            Mail::to($email)->send(new SendOTPMail($otp));
        } catch (\Exception $e) {
            // Log the mail sending failure but continue in local development, writing to log
            \Log::error("Failed sending OTP email: " . $e->getMessage());
        }

        // Store temporary session key in Cache to identify this OTP process
        $tempToken = Str::random(40);
        Cache::put('otp_session_' . $tempToken, [
            'user_id' => $user->id,
            'remember_me' => $request->boolean('remember_me', false),
            'ip' => $request->ip(),
        ], now()->addMinutes(5));

        return response()->json([
            'status' => 'otp_sent',
            'temp_token' => $tempToken,
            'message' => 'Kode OTP telah dikirimkan ke email ' . obfuscate_email($email)
        ]);
    }

    /**
     * Stage 2: Verify OTP -> Issues JWT Access Token
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'temp_token' => 'required|string',
            'otp_code' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sessionData = Cache::get('otp_session_' . $request->temp_token);
        if (!$sessionData) {
            return response()->json([
                'errors' => ['otp_code' => ['Sesi verifikasi OTP telah kedaluwarsa. Silakan login kembali.']]
            ], 422);
        }

        $userId = $sessionData['user_id'];
        $rememberMe = $sessionData['remember_me'];

        // Retrieve latest OTP record
        $otpRecord = LoginOtp::where('user_id', $userId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$otpRecord || !Hash::check($request->otp_code, $otpRecord->otp_hash)) {
            return response()->json([
                'errors' => ['otp_code' => ['Kode OTP salah atau sudah kedaluwarsa.']]
            ], 422);
        }

        // Mark OTP as used
        $otpRecord->update([
            'used_at' => now(),
            'verified_at' => now()
        ]);

        // Find user
        $user = User::with(['role', 'employee'])->find($userId);

        if ($user->status !== 'active') {
            return response()->json([
                'errors' => ['otp_code' => ['Akun Anda dinonaktifkan selama verifikasi OTP.']]
            ], 403);
        }

        // Build active session
        $sessionToken = Str::random(64);
        $sessionExpiry = $rememberMe ? null : now()->addMinutes(3);

        $session = UserSession::create([
            'user_id' => $user->id,
            'session_token' => $sessionToken,
            'remember_me' => $rememberMe,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity_at' => now(),
            'expires_at' => $sessionExpiry
        ]);

        // Update last login
        $user->update([
            'last_login_at' => now()
        ]);

        // Create log entry
        activity_log('auth', 'login', 'Pengguna berhasil login', $user);

        // Generate JWT (JWT will expire in 24 hours, but database session limits it to 3 mins inactivity)
        $token = JWTService::generateToken($user->id, $user->username, $user->role->code, $sessionToken, 86400);

        // Remove temp token from cache
        Cache::forget('otp_session_' . $request->temp_token);

        return response()->json([
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->code,
                'employee_id' => $user->employee_id,
            ]
        ]);
    }

    /**
     * Get Current Profile Details
     */
    public function me(Request $request)
    {
        $user = $request->get('current_user');
        
        // Load relationships
        $user->load(['role', 'employee.position', 'employee.department', 'employee.district.regency.province', 'employee.educations']);
        
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $session = $request->get('current_session');
        $user = $request->get('current_user');

        if ($session) {
            $session->update([
                'logged_out_at' => now()
            ]);
        }

        if ($user) {
            $user->update([
                'last_logout_at' => now()
            ]);
            activity_log('auth', 'logout', 'Pengguna melakukan logout', $user);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout'
        ]);
    }

    /**
     * Change password (My Profile)
     */
    public function changePassword(Request $request)
    {
        $user = $request->get('current_user');

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/^\S*$/', // no spaces
                'regex:/[A-Z]/', // at least one uppercase
                'regex:/[a-z]/', // at least one lowercase
                'regex:/[!@#$%^&*(),.?":{}|<>]/', // at least one special char
            ],
            'confirm_password' => 'required|same:new_password'
        ], [
            'new_password.min' => 'Password minimal 8 karakter.',
            'new_password.regex' => 'Password tidak boleh mengandung spasi dan harus memiliki minimal 1 huruf besar, 1 huruf kecil, serta 1 karakter khusus.',
            'confirm_password.same' => 'Konfirmasi password tidak cocok dengan password baru.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['Password saat ini salah.']]
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now()
        ]);

        activity_log('profile', 'update', 'Pengguna merubah password profil dirinya', $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui'
        ]);
    }
}

/**
 * Obfuscate Email for response privacy
 */
function obfuscate_email($email) {
    $parts = explode("@", $email);
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);
    
    if ($len <= 2) {
        return $name[0] . "*@" . $domain;
    }
    
    $keep = 2;
    $obfuscated = substr($name, 0, $keep) . str_repeat("*", $len - $keep);
    return $obfuscated . "@" . $domain;
}
