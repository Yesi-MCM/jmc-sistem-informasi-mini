<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Exports\EmployeesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with pagination, search, and filters.
     */
    public function index(Request $request)
    {
        $query = Employee::with(['position', 'department', 'district.regency.province']);

        // 1. Search (NIP, Name, or Job Title)
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'LIKE', '%' . $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%')
                  ->orWhereHas('position', function ($p) use ($search) {
                      $p->where('name', 'LIKE', '%' . $search . '%');
                  });
            });
        }

        // 2. Filter by Position (multi-select IDs)
        if ($request->filled('positions')) {
            $positions = is_array($request->positions) ? $request->positions : json_decode($request->positions, true);
            if (!empty($positions)) {
                $query->whereIn('position_id', $positions);
            }
        }

        // 3. Filter by Contract Status (employment_type: pkwtt, pkwt, magang)
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        // 4. Filter by Status (active/inactive)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 5. Filter by Work Tenure (Masa Kerja in Years)
        if ($request->filled('min_tenure') || $request->filled('max_tenure')) {
            $minTenure = $request->query('min_tenure');
            $maxTenure = $request->query('max_tenure');

            // Calculate date ranges based on joined_at
            if ($minTenure !== null && $minTenure !== '') {
                $maxJoinedDate = now()->subYears((int)$minTenure)->endOfDay();
                $query->where('joined_at', '<=', $maxJoinedDate);
            }
            if ($maxTenure !== null && $maxTenure !== '') {
                $minJoinedDate = now()->subYears((int)$maxTenure + 1)->startOfDay();
                $query->where('joined_at', '>=', $minJoinedDate);
            }
        }

        // 6. Sorting
        $sortField = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');

        $allowedSorts = ['nip', 'name', 'joined_at', 'position_id'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->query('per_page', 10);
        $employees = $query->paginate($perPage);

        // Append calculated attributes to pagination collection
        $employees->getCollection()->transform(function ($emp) {
            $emp->age = $emp->age;
            $emp->work_tenure = $emp->work_tenure;
            return $emp;
        });

        return response()->json($employees);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|min:8|regex:/^[0-9]+$/|unique:employees,nip',
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9\s\']+$/' // Letters, numbers, spaces, single quotes
            ],
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|regex:/^\+?[0-9\-\s]+$/',
            'birth_place' => 'required|string',
            'birth_date' => 'required|date_format:d/m/Y',
            'marital_status' => 'required|in:kawin,tidak kawin',
            'children_count' => 'required|integer|min:0|max:99',
            'joined_at' => 'required|date_format:d/m/Y',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'employment_type' => 'required|in:pkwtt,pkwt,magang',
            'gender' => 'required|in:pria,wanita',
            'distance_km' => 'required|numeric|min:0|max:999.99',
            'district_id' => 'required|exists:districts,id',
            'full_address' => 'required|string',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048', // max 2MB
            'educations' => 'nullable|array',
            'educations.*.education_level' => 'required|string',
            'educations.*.school_name' => 'required|string',
            'educations.*.graduation_year' => 'required|integer|min:1900|max:2100',
        ], [
            'nip.regex' => 'NIP hanya boleh terdiri dari angka.',
            'nip.min' => 'NIP minimal 8 karakter.',
            'name.regex' => 'Nama hanya boleh berupa huruf, angka, tanda petik satu, dan spasi.',
            'phone.regex' => 'Format nomor HP tidak valid.',
            'photo.max' => 'Foto tidak boleh lebih dari 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Format dates
        $birthDate = Carbon::createFromFormat('d/m/Y', $request->birth_date)->format('Y-m-d');
        $joinedAt = Carbon::createFromFormat('d/m/Y', $request->joined_at)->format('Y-m-d');

        // Handle Photo Upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        DB::beginTransaction();
        try {
            // Create employee record
            $employee = Employee::create(array_merge($request->all(), [
                'birth_date' => $birthDate,
                'joined_at' => $joinedAt,
                'photo_path' => $photoPath,
                'created_by' => $request->get('current_user')?->id
            ]));

            // Insert education history
            if ($request->filled('educations')) {
                $educations = is_array($request->educations) ? $request->educations : json_decode($request->educations, true);
                foreach ($educations as $index => $edu) {
                    EmployeeEducation::create([
                        'employee_id' => $employee->id,
                        'education_level' => $edu['education_level'],
                        'school_name' => $edu['school_name'],
                        'graduation_year' => $edu['graduation_year'],
                        'sort_order' => $edu['sort_order'] ?? $index
                    ]);
                }
            }

            // Log activity
            activity_log('pegawai', 'create', 'Menambah data pegawai baru: ' . $employee->name, null, $employee, null, $employee->toArray());

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => $employee
            ], 210); // 210 or 201 Created

        } catch (\Exception $e) {
            DB::rollBack();
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified employee.
     */
    public function show($id)
    {
        $employee = Employee::with(['position', 'department', 'district.regency.province', 'educations'])->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        // Attach accessors
        $employee->age = $employee->age;
        $employee->work_tenure = $employee->work_tenure;

        return response()->json($employee);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|min:8|regex:/^[0-9]+$/|unique:employees,nip,' . $id,
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9\s\']+$/'
            ],
            'email' => 'required|email|unique:employees,email,' . $id,
            'phone' => 'required|string|regex:/^\+?[0-9\-\s]+$/',
            'birth_place' => 'required|string',
            'birth_date' => 'required|date_format:d/m/Y',
            'marital_status' => 'required|in:kawin,tidak kawin',
            'children_count' => 'required|integer|min:0|max:99',
            'joined_at' => 'required|date_format:d/m/Y',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'employment_type' => 'required|in:pkwtt,pkwt,magang',
            'gender' => 'required|in:pria,wanita',
            'distance_km' => 'required|numeric|min:0|max:999.99',
            'district_id' => 'required|exists:districts,id',
            'full_address' => 'required|string',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'educations' => 'nullable|array',
            'educations.*.education_level' => 'required|string',
            'educations.*.school_name' => 'required|string',
            'educations.*.graduation_year' => 'required|integer|min:1900|max:2100',
        ], [
            'nip.regex' => 'NIP hanya boleh terdiri dari angka.',
            'nip.min' => 'NIP minimal 8 karakter.',
            'name.regex' => 'Nama hanya boleh berupa huruf, angka, tanda petik satu, dan spasi.',
            'phone.regex' => 'Format nomor HP tidak valid.',
            'photo.max' => 'Foto tidak boleh lebih dari 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldData = $employee->toArray();

        // Format dates
        $birthDate = Carbon::createFromFormat('d/m/Y', $request->birth_date)->format('Y-m-d');
        $joinedAt = Carbon::createFromFormat('d/m/Y', $request->joined_at)->format('Y-m-d');

        // Handle Photo Upload
        $photoPath = $employee->photo_path;
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($employee->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        DB::beginTransaction();
        try {
            // Update employee record
            $employee->update(array_merge($request->all(), [
                'birth_date' => $birthDate,
                'joined_at' => $joinedAt,
                'photo_path' => $photoPath,
                'updated_by' => $request->get('current_user')?->id
            ]));

            // Sync educational history
            // For simplicity, we delete the existing and recreate
            EmployeeEducation::where('employee_id', $employee->id)->delete();
            
            if ($request->filled('educations')) {
                $educations = is_array($request->educations) ? $request->educations : json_decode($request->educations, true);
                foreach ($educations as $index => $edu) {
                    EmployeeEducation::create([
                        'employee_id' => $employee->id,
                        'education_level' => $edu['education_level'],
                        'school_name' => $edu['school_name'],
                        'graduation_year' => $edu['graduation_year'],
                        'sort_order' => $edu['sort_order'] ?? $index
                    ]);
                }
            }

            // Log activity
            activity_log('pegawai', 'update', 'Memperbarui data pegawai: ' . $employee->name, null, $employee, $oldData, $employee->toArray());

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Pegawai berhasil diperbarui',
                'data' => $employee
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete an employee.
     */
    public function destroy(Request $request, $id)
    {
        $employee = Employee::with('user')->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        // Admin HRD cannot delete superadmin employee
        if ($employee->user && $employee->user->role->code === 'superadmin') {
            return response()->json([
                'message' => 'Anda tidak diperkenankan menghapus data pegawai yang memiliki hak akses Superadmin.'
            ], 403);
        }

        $oldData = $employee->toArray();
        $employee->delete();

        activity_log('pegawai', 'delete', 'Menghapus data pegawai: ' . $employee->name, null, $employee, $oldData);

        return response()->json([
            'status' => 'success',
            'message' => 'Pegawai berhasil dihapus'
        ]);
    }

    /**
     * Bulk status update for selected employees.
     */
    public function changeStatusBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:employees,id',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ids = $request->ids;
        $status = $request->status;

        DB::beginTransaction();
        try {
            Employee::whereIn('id', $ids)->update(['status' => $status]);

            // If updating status to inactive, force logout linked users!
            if ($status === 'inactive') {
                $userIds = \App\Models\User::whereIn('employee_id', $ids)->pluck('id');
                if ($userIds->isNotEmpty()) {
                    \App\Models\UserSession::whereIn('user_id', $userIds)
                        ->whereNull('logged_out_at')
                        ->update(['logged_out_at' => now()]);
                }
            }

            activity_log('pegawai', 'update', 'Mengubah status massal (' . $status . ') untuk pegawai ID: ' . implode(', ', $ids));

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Status pegawai berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Bulk deletion of selected employees.
     */
    public function deleteBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ids = $request->ids;

        // Check if any of these employee IDs are linked to a superadmin account
        $hasSuperadmin = Employee::whereIn('id', $ids)
            ->whereHas('user.role', function ($q) {
                $q->where('code', 'superadmin');
            })->exists();

        if ($hasSuperadmin) {
            return response()->json([
                'message' => 'Penghapusan massal dibatalkan karena terdapat pegawai dengan hak akses Superadmin.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            Employee::whereIn('id', $ids)->delete();

            activity_log('pegawai', 'delete', 'Menghapus massal data pegawai ID: ' . implode(', ', $ids));

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Daftar pegawai berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export employees list to Excel
     */
    public function exportExcel(Request $request)
    {
        // Re-use same query constraints as index
        $query = Employee::with(['position', 'department']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'LIKE', '%' . $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%');
            });
        }
        if ($request->filled('positions')) {
            $positions = is_array($request->positions) ? $request->positions : json_decode($request->positions, true);
            if (!empty($positions)) {
                $query->whereIn('position_id', $positions);
            }
        }
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        activity_log('pegawai', 'read', 'Mengunduh laporan pegawai format Excel');

        return Excel::download(new EmployeesExport($query), 'data_pegawai.xlsx');
    }

    /**
     * Export employees list to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Employee::with(['position', 'department']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'LIKE', '%' . $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%');
            });
        }
        if ($request->filled('positions')) {
            $positions = is_array($request->positions) ? $request->positions : json_decode($request->positions, true);
            if (!empty($positions)) {
                $query->whereIn('position_id', $positions);
            }
        }
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->get();

        activity_log('pegawai', 'read', 'Mengunduh laporan pegawai format PDF');

        // Set paper format to A4 Landscape for the table list
        $pdf = Pdf::loadView('pdf.employees_list', compact('employees'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('daftar_pegawai.pdf');
    }

    /**
     * Export single employee detail profile to PDF
     */
    public function exportDetailPdf($id)
    {
        $employee = Employee::with(['position', 'department', 'district.regency.province', 'educations'])->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        activity_log('pegawai', 'read', 'Mengunduh detail pegawai format PDF: ' . $employee->name, null, $employee);

        // Set paper format to A4 Portrait for detailed profiles
        $pdf = Pdf::loadView('pdf.employee_detail', compact('employee'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('detail_pegawai_' . $employee->nip . '.pdf');
    }

    /**
     * Get dashboard statistics for Manager HRD role.
     */
    public function getDashboardStats()
    {
        $totalActive = Employee::where('status', 'active')->count();
        $totalPkwt = Employee::where('status', 'active')->where('employment_type', 'pkwt')->count();
        $totalPkwtt = Employee::where('status', 'active')->where('employment_type', 'pkwtt')->count();
        $totalMagang = Employee::where('status', 'active')->where('employment_type', 'magang')->count();

        $male = Employee::where('status', 'active')->where('gender', 'pria')->count();
        $female = Employee::where('status', 'active')->where('gender', 'wanita')->count();

        $newest = Employee::with(['position'])
            ->where('status', 'active')
            ->orderBy('joined_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($emp) {
                return [
                    'nip' => $emp->nip,
                    'name' => $emp->name,
                    'joined_at' => $emp->joined_at ? $emp->joined_at->format('d/m/Y') : '-',
                    'position' => $emp->position ? $emp->position->name : '-',
                    'employment_type' => strtoupper($emp->employment_type),
                    'photo' => $emp->photo_path
                ];
            });

        return response()->json([
            'total_active' => $totalActive,
            'total_pkwt' => $totalPkwt,
            'total_pkwtt' => $totalPkwtt,
            'total_magang' => $totalMagang,
            'gender' => [
                'male' => $male,
                'female' => $female
            ],
            'newest_employees' => $newest
        ]);
    }

    /**
     * Get metadata for employee form select options.
     */
    public function getFormMeta()
    {
        $positions = \App\Models\Position::all();
        $departments = \App\Models\Department::all();
        return response()->json([
            'positions' => $positions,
            'departments' => $departments
        ]);
    }
}
