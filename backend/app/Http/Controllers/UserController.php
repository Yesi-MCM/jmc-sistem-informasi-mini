<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of system users with pagination.
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'employee']);

        // Search by name or username
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('username', 'LIKE', '%' . $search . '%');
            });
        }

        // Sorting
        $sortField = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');

        $allowedSorts = ['name', 'username', 'status'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate($request->query('per_page', 10));

        return response()->json($users);
    }

    /**
     * Store a newly created system user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'role_id' => 'required|exists:roles,id',
            'username' => [
                'required',
                'string',
                'min:6',
                'unique:users,username',
                'regex:/^[a-z0-9]+$/', // lowercase alphanumeric only, no spaces
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^\S*$/', // no spaces
                'regex:/[A-Z]/', // at least one uppercase
                'regex:/[a-z]/', // at least one lowercase
                'regex:/[!@#$%^&*(),.?":{}|<>]/', // at least one special char
            ],
            'status' => 'required|in:active,inactive'
        ], [
            'username.regex' => 'Username hanya boleh terdiri dari huruf kecil dan angka, tanpa spasi.',
            'username.min' => 'Username minimal 6 karakter.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.regex' => 'Password tidak boleh mengandung spasi dan harus memiliki minimal 1 huruf besar, 1 huruf kecil, serta 1 karakter khusus.',
            'employee_id.unique' => 'Pegawai terpilih sudah memiliki akun pengguna.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::find($request->employee_id);

        DB::beginTransaction();
        try {
            $user = User::create([
                'employee_id' => $request->employee_id,
                'role_id' => $request->role_id,
                'name' => $employee->name,
                'username' => $request->username,
                'email' => $employee->email,
                'cellphone' => $employee->phone,
                'password' => Hash::make($request->password),
                'status' => $request->status,
            ]);

            activity_log('user', 'create', 'Membuat akun user baru: ' . $user->username, null, $user);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil ditambahkan',
                'data' => $user
            ], 210); // 210 or 201 Created

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display user details.
     */
    public function show($id)
    {
        $user = User::with(['role', 'employee'])->find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldData = $user->toArray();
        $currentUser = $request->get('current_user');

        // Check if updating self status to inactive
        if ($user->id === $currentUser->id && $request->status === 'inactive') {
            return response()->json(['message' => 'Anda tidak diperkenankan menonaktifkan akun Anda sendiri.'], 403);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'role_id' => $request->role_id,
                'status' => $request->status,
            ]);

            // If user status updated to inactive, force logout immediately
            if ($request->status === 'inactive') {
                UserSession::where('user_id', $user->id)
                    ->whereNull('logged_out_at')
                    ->update(['logged_out_at' => now()]);
            }

            activity_log('user', 'update', 'Memperbarui akun user: ' . $user->username, null, $user, $oldData, $user->toArray());

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil diperbarui',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete user account.
     */
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $currentUser = $request->get('current_user');

        // Cannot delete self
        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Anda tidak diperkenankan menghapus akun Anda sendiri.'], 403);
        }

        $oldData = $user->toArray();
        $user->delete();

        // Invalidate sessions immediately
        UserSession::where('user_id', $id)
            ->whereNull('logged_out_at')
            ->update(['logged_out_at' => now()]);

        activity_log('user', 'delete', 'Menghapus akun user: ' . $user->username, null, $user, $oldData);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dihapus'
        ]);
    }

    /**
     * Autocomplete employee search for creating new users.
     * Searches active employees who do not have a user account yet.
     */
    public function searchUnregisteredEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $search = $request->query('q');

        // Active employees who don't have a user record
        $employees = Employee::where('status', 'active')
            ->where(function($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('nip', 'LIKE', '%' . $search . '%');
            })
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('users')
                      ->whereColumn('users.employee_id', 'employees.id')
                      ->whereNull('users.deleted_at');
            })
            ->limit(20)
            ->get();

        $results = $employees->map(function ($emp) {
            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'nip' => $emp->nip,
                'email' => $emp->email,
                'phone' => $emp->phone,
                'position_name' => $emp->position ? $emp->position->name : '-',
                'department_name' => $emp->department ? $emp->department->name : '-',
                'label' => sprintf('%s (%s) - %s', $emp->name, $emp->nip, $emp->position ? $emp->position->name : '')
            ];
        });

        return response()->json([
            'data' => $results
        ]);
    }

    /**
     * Get list of roles for select dropdowns.
     */
    public function getRoles()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    /**
     * Get detail of a specific role with its module permissions.
     */
    public function getRoleDetails($id)
    {
        $role = Role::with('permissions.module')->find($id);
        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }
        return response()->json($role);
    }
}
