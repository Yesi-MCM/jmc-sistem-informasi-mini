<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserSession;
use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$modules): Response
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token otentikasi tidak disediakan.'
            ], 401);
        }

        $token = substr($authHeader, 7);
        $payload = JWTService::decodeToken($token);

        if (!$payload) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token otentikasi tidak valid atau kedaluwarsa.'
            ], 401);
        }

        // Validate session in database
        $session = UserSession::where('session_token', $payload->session_token)
            ->whereNull('logged_out_at')
            ->first();

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi Anda telah berakhir.'
            ], 401);
        }

        // Check for 3-minute inactivity if not remember_me
        if (!$session->remember_me) {
            $inactivityLimit = 3 * 60; // 3 minutes in seconds
            $timeSinceLastActivity = time() - $session->last_activity_at->timestamp;

            if ($timeSinceLastActivity > $inactivityLimit) {
                // Invalidate session
                $session->update([
                    'logged_out_at' => now()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi Anda telah kedaluwarsa karena tidak ada aktivitas selama 3 menit.'
                ], 401);
            }
        }

        // Update last activity timestamp
        $session->update([
            'last_activity_at' => now()
        ]);

        // Find user
        $user = User::with('role')->find($payload->sub);
        if (!$user || $user->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda dinonaktifkan atau tidak terdaftar.'
            ], 403);
        }

        // Check RBAC permission for specific module if requested in middleware
        if (!empty($modules)) {
            $moduleCode = $modules[0];
            $actionRequired = $modules[1] ?? 'read'; // 'create', 'read', 'update', 'delete'

            // Superadmin has full access to user and role, and read-only to role/log
            // Let's implement checkRolePermission helper
            if (!self::checkRolePermission($user->role_id, $moduleCode, $actionRequired, $user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk aksi ini pada modul ' . $moduleCode
                ], 403);
            }
        }

        // Attach variables to request
        $request->attributes->set('current_user', $user);
        $request->attributes->set('current_session', $session);

        // Alias for controllers
        $request->merge([
            'current_user' => $user,
            'current_session' => $session
        ]);

        return $next($request);
    }

    /**
     * Check if a role has permission for a module action
     */
    private static function checkRolePermission(int $roleId, string $moduleCode, string $action, int $userId): bool
    {
        // Simple hardcoded mapping or database lookup
        $permission = \App\Models\RolePermission::where('role_id', $roleId)
            ->whereHas('module', function($q) use ($moduleCode) {
                $q->where('code', $moduleCode);
            })->first();

        if (!$permission || !$permission->can_access) {
            return false;
        }

        switch ($action) {
            case 'create':
                return $permission->can_create;
            case 'read':
                return $permission->read_scope !== 'no';
            case 'update':
                return $permission->update_scope !== 'no';
            case 'delete':
                return $permission->delete_scope !== 'no';
            default:
                return false;
        }
    }
}
