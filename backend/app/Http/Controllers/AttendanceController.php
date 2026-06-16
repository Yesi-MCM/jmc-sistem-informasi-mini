<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Jobs\ProcessAttendanceImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of aggregated monthly attendance summaries.
     * Default period is N-1 (previous month).
     */
    public function index(Request $request)
    {
        $year = $request->query('year', now()->subMonth()->year);
        $month = $request->query('month', now()->subMonth()->month);

        // Fetch summaries with employee details
        $summaries = AttendanceSummary::with(['employee.position', 'employee.department'])
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->get();

        // If no summaries exist for this period, let's dynamically build them based on attendance records
        if ($summaries->isEmpty()) {
            $employees = Employee::where('status', 'active')->get();
            foreach ($employees as $employee) {
                // Trigger summary recalculation for the period
                $this->recalculateSummary($employee->id, $year, $month);
            }

            // Refetch
            $summaries = AttendanceSummary::with(['employee.position', 'employee.department'])
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->get();
        }

        // Map and format results
        $results = $summaries->map(function ($s, $index) {
            return [
                'no' => $index + 1,
                'employee_id' => $s->employee_id,
                'nip' => $s->employee ? $s->employee->nip : '-',
                'name' => $s->employee ? $s->employee->name : '-',
                'position' => $s->employee && $s->employee->position ? $s->employee->position->name : '-',
                'hadir' => (float) $s->hadir,
                'status_hadir' => $s->status_hadir,
                'cuti' => (float) $s->cuti,
                'kuota_cuti' => (float) $s->kuota_cuti,
                'izin' => (float) $s->izin,
                'kuota_izin' => (float) $s->kuota_izin,
                'unpaid_leave' => (float) $s->unpaid_leave,
                'kuota_unpaid_leave' => (float) $s->kuota_unpaid_leave,
            ];
        });

        return response()->json([
            'year' => $year,
            'month' => $month,
            'data' => $results
        ]);
    }

    /**
     * Display daily attendance log for a specific employee in a given month.
     */
    public function show(Request $request, $employeeId)
    {
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        $employee = Employee::with(['position', 'department'])->find($employeeId);
        if (!$employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date', 'asc')
            ->get();

        $formattedLogs = $attendances->map(function ($att) {
            return [
                'date' => $att->attendance_date->format('d/m/Y'),
                'checkin_at' => $att->checkin_at ?: '-',
                'checkout_at' => $att->checkout_at ?: '-',
                'checkin_location' => $att->checkin_location ?: '-',
                'checkout_location' => $att->checkout_location ?: '-',
                'attendance_type' => ucfirst($att->attendance_type),
                'duration' => (float) $att->duration_hours,
                'status' => $att->status === 'terpenuhi' ? 'Terpenuhi' : 'Tidak terpenuhi',
                'verification_status' => $att->verification_status ?: 'Disetujui',
                'verified_by_role' => $att->verified_by_role ?: 'HRD',
                'remarks' => $att->remarks ?: '-',
            ];
        });

        return response()->json([
            'employee' => [
                'name' => $employee->name,
                'nip' => $employee->nip,
                'position' => $employee->position ? $employee->position->name : '-',
                'department' => $employee->department ? $employee->department->name : '-',
            ],
            'year' => $year,
            'month' => $month,
            'logs' => $formattedLogs
        ]);
    }

    /**
     * Generate and download Excel import template prefilled with sample rows
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Presensi');

        // Headers
        $headers = [
            'NIP',
            'Tanggal (YYYY-MM-DD)',
            'Jam Masuk (HH:MM:SS)',
            'Jam Keluar (HH:MM:SS)',
            'Lokasi Masuk',
            'Lokasi Keluar',
            'Kehadiran (hadir/cuti/izin/unpaid_leave)',
            'Verifikasi (Disetujui/Ditolak)',
            'Verifikator (Lead/Manager/HRD)',
            'Keterangan'
        ];

        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        }

        // Sample Rows using seeded NIPs
        $sampleData = [
            ['19900101', '2026-05-01', '08:00:00', '17:00:00', 'Gedung Utama', 'Gedung Utama', 'hadir', 'Disetujui', 'HRD', 'Masuk normal'],
            ['19900101', '2026-05-02', '08:10:00', '17:00:00', 'Gedung A', 'Gedung A', 'hadir', 'Disetujui', 'HRD', 'Masuk terlambat 10 menit (masuk normal)'],
            ['19920202', '2026-05-01', '08:30:00', '17:30:00', 'Gedung B', 'Gedung B', 'hadir', 'Disetujui', 'Manager', 'Terlambat >15 m, lembur ganti waktu (halfday)'],
            ['19920202', '2026-05-02', '', '', '', '', 'cuti', 'Disetujui', 'HRD', 'Cuti tahunan'],
            ['19950303', '2026-05-01', '08:00:00', '17:00:00', 'Gedung Utama', 'Gedung A', 'hadir', 'Ditolak', 'Lead', 'Beda lokasi checkin/checkout (tidak masuk)'],
        ];

        foreach ($sampleData as $rowIndex => $rowData) {
            $rowNum = $rowIndex + 2;
            foreach ($rowData as $colIndex => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($colLetter . $rowNum, $val);
            }
        }

        // Auto size columns
        foreach (range(1, count($headers)) as $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        activity_log('presensi', 'read', 'Mengunduh template Excel presensi');

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template_presensi.xlsx"',
            'Cache-Control' => 'max-age=0'
        ]);
    }

    /**
     * Upload and import Excel presensi
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // max 5MB
            'period_year' => 'required|integer',
            'period_month' => 'required|integer|min:1|max:12'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->get('current_user');

        // Store file temporarily
        $file = $request->file('file');
        $tempPath = $file->storeAs('temp_imports', Str::random(40) . '.' . $file->getClientOriginalExtension());
        $fullPath = storage_path('app/' . $tempPath);

        // Create import record
        $import = AttendanceImport::create([
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'period_year' => $request->period_year,
            'period_month' => $request->period_month,
            'status' => 'queued',
            'total_rows' => 0,
            'processed_rows' => 0,
        ]);

        activity_log('presensi', 'create', 'Mengunggah file presensi: ' . $file->getClientOriginalName() . ' untuk periode ' . $request->period_month . '/' . $request->period_year, $user, $import);

        // Dispatch background job
        ProcessAttendanceImport::dispatch($import->id, $fullPath);

        return response()->json([
            'status' => 'success',
            'message' => 'Proses import presensi berhasil dijadwalkan di background. Silakan tunggu beberapa saat.',
            'import_id' => $import->id
        ]);
    }

    /**
     * Check background import task status
     */
    public function getImportStatus($id)
    {
        $import = AttendanceImport::find($id);
        if (!$import) {
            return response()->json(['message' => 'Tugas import tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => $import->status,
            'processed' => $import->processed_rows,
            'total' => $import->total_rows,
            'error' => $import->error_message
        ]);
    }

    /**
     * Helper to trigger manual summary recalculation
     */
    private function recalculateSummary(int $employeeId, int $year, int $month): void
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();

        $hadir = 0.0;
        $cuti = 0.0;
        $izin = 0.0;
        $unpaid = 0.0;

        foreach ($attendances as $att) {
            if ($att->attendance_type === 'hadir') {
                if ($att->status === 'terpenuhi') {
                    $checkinTime = Carbon::parse($att->checkin_at)->format('H:i:s');
                    if ($checkinTime > '08:15:00') {
                        $hadir += 0.5;
                    } else {
                        $hadir += 1.0;
                    }
                }
            } elseif ($att->attendance_type === 'cuti') {
                $cuti += 1.0;
            } elseif ($att->attendance_type === 'izin') {
                $izin += 1.0;
            } elseif ($att->attendance_type === 'unpaid_leave') {
                $unpaid += 1.0;
            }
        }

        $statusHadir = $hadir >= 19.0 ? 'Terpenuhi' : 'Tidak terpenuhi';

        AttendanceSummary::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'period_year' => $year,
                'period_month' => $month
            ],
            [
                'hadir' => $hadir,
                'cuti' => $cuti,
                'kuota_cuti' => 12.0,
                'izin' => $izin,
                'kuota_izin' => 5.0,
                'unpaid_leave' => $unpaid,
                'kuota_unpaid_leave' => 10.0,
                'status_hadir' => $statusHadir,
                'calculated_at' => now()
            ]
        );
    }
}
