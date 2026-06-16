<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessAttendanceImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(int $importId, string $filePath)
    {
        $this->importId = $importId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $import = AttendanceImport::find($this->importId);
        if (!$import) {
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            $spreadsheet = IOFactory::load($this->filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Assume header is first row
            // Columns: NIP, Tanggal, Jam Masuk, Jam Keluar, Lokasi Masuk, Lokasi Keluar, Kehadiran, Verifikasi, Verifikator, Keterangan
            $header = array_map('trim', $rows[0]);
            
            $totalRows = count($rows) - 1;
            $import->update(['total_rows' => $totalRows]);

            $processedRows = 0;
            $affectedEmployees = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $nip = trim($row[0]);
                $dateStr = trim($row[1]);
                $checkinAt = trim($row[2]);
                $checkoutAt = trim($row[3]);
                $checkinLoc = trim($row[4]);
                $checkoutLoc = trim($row[5]);
                $type = strtolower(trim($row[6] ?? 'hadir'));
                $verificationStatus = trim($row[7] ?? 'Disetujui');
                $verifiedByRole = trim($row[8] ?? 'HRD');
                $remarks = trim($row[9] ?? '');

                // Find employee by NIP
                $employee = Employee::where('nip', $nip)->first();
                if (!$employee) {
                    continue; // Skip if employee doesn't exist
                }

                // Format attendance date
                $attendanceDate = null;
                try {
                    // Try parsing Excel date or string
                    if (is_numeric($dateStr)) {
                        $attendanceDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr))->format('Y-m-d');
                    } else {
                        $attendanceDate = Carbon::parse($dateStr)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue; // Skip row with invalid date
                }

                // Calculate duration and status
                $calc = $this->calculateDailyAttendance($checkinAt, $checkoutAt, $checkinLoc, $checkoutLoc, $type);

                // Create or update attendance record
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'attendance_import_id' => $import->id,
                        'checkin_at' => $type === 'hadir' && $checkinAt ? Carbon::parse($checkinAt)->format('H:i:s') : null,
                        'checkout_at' => $type === 'hadir' && $checkoutAt ? Carbon::parse($checkoutAt)->format('H:i:s') : null,
                        'checkin_location' => $type === 'hadir' ? $checkinLoc : null,
                        'checkout_location' => $type === 'hadir' ? $checkoutLoc : null,
                        'attendance_type' => $type ?: 'hadir',
                        'duration_hours' => $calc['duration'],
                        'status' => $calc['status'],
                        'verification_status' => $verificationStatus ?: 'Disetujui',
                        'verified_by_role' => $verifiedByRole ?: 'HRD',
                        'remarks' => $remarks
                    ]
                );

                $processedRows++;
                $import->update(['processed_rows' => $processedRows]);

                // Track affected employees for monthly summary calculation
                $carbonDate = Carbon::parse($attendanceDate);
                $periodKey = $employee->id . '_' . $carbonDate->year . '_' . $carbonDate->month;
                $affectedEmployees[$periodKey] = [
                    'employee_id' => $employee->id,
                    'year' => $carbonDate->year,
                    'month' => $carbonDate->month
                ];
            }

            // Recalculate summaries for affected employees/periods
            foreach ($affectedEmployees as $affected) {
                $this->recalculateMonthlySummary($affected['employee_id'], $affected['year'], $affected['month']);
            }

            $import->update([
                'status' => 'completed',
                'finished_at' => now()
            ]);

            // Delete temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now()
            ]);
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        }
    }

    /**
     * Daily attendance duration and status helper
     */
    private function calculateDailyAttendance(?string $checkinAt, ?string $checkoutAt, ?string $checkinLoc, ?string $checkoutLoc, string $type = 'hadir'): array
    {
        if ($type !== 'hadir' || !$checkinAt || !$checkoutAt) {
            return [
                'duration' => 0.0,
                'status' => 'tidak_terpenuhi'
            ];
        }

        if ($checkinLoc !== $checkoutLoc) {
            return [
                'duration' => 0.0,
                'status' => 'tidak_terpenuhi'
            ];
        }

        $in = Carbon::parse($checkinAt);
        $out = Carbon::parse($checkoutAt);

        // Cap checkin time for duration calculation
        $effectiveIn = $in->copy();
        $inTimeStr = $in->format('H:i:s');

        if ($inTimeStr <= '08:15:00') {
            // Lateness <= 15 minutes is counted as normal (start at 08:00)
            $effectiveIn->setTime(8, 0, 0);
        } else {
            // Lateness > 15 minutes starts at actual checkin time
            $effectiveIn = $in->copy();
        }

        // Calculate work duration in seconds
        $seconds = $out->diffInSeconds($effectiveIn);

        // Deduct 1 hour break (12:00 - 13:00) if the working interval covers it
        $breakStart = $effectiveIn->copy()->setTime(12, 0, 0);
        $breakEnd = $effectiveIn->copy()->setTime(13, 0, 0);

        if ($effectiveIn->lessThanOrEqualTo($breakStart) && $out->greaterThanOrEqualTo($breakEnd)) {
            $seconds -= 3600;
        }

        $duration = round($seconds / 3600, 1);
        if ($duration < 0) {
            $duration = 0.0;
        }

        // Determine status
        // Minimal jam kerja normal adalah 8 jam, kurang dari itu, maka status kehadirannya "Tidak terpenuhi"
        $status = $duration >= 8.0 ? 'terpenuhi' : 'tidak_terpenuhi';

        return [
            'duration' => $duration,
            'status' => $status
        ];
    }

    /**
     * Recalculate monthly summary for an employee
     */
    private function recalculateMonthlySummary(int $employeeId, int $year, int $month): void
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
                    // Check if they were late > 15 mins
                    $checkinTime = Carbon::parse($att->checkin_at)->format('H:i:s');
                    if ($checkinTime > '08:15:00') {
                        $hadir += 0.5; // halfday weight
                    } else {
                        $hadir += 1.0; // full day weight
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

        // Status Hadir: "Terpenuhi" if presence quota is met. We use 19 days as the threshold.
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
                'kuota_cuti' => 12.0, // default yearly/monthly allocations
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
