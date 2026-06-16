<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceSummary;
use App\Models\TransportAllowanceSetting;
use App\Models\TransportAllowancePeriod;
use App\Models\TransportAllowanceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransportAllowanceController extends Controller
{
    /**
     * Get the currently active transport allowance setting.
     */
    public function getSetting()
    {
        $setting = TransportAllowanceSetting::where('is_active', true)->first();

        if (!$setting) {
            // Fallback default
            return response()->json([
                'base_fare' => 5000.00,
                'effective_start' => '2026-01-01',
                'min_km' => 5.00,
                'max_km' => 25.00,
                'is_active' => true
            ]);
        }

        return response()->json($setting);
    }

    /**
     * Update/create transport allowance setting (maintains history).
     */
    public function updateSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'base_fare' => 'required|numeric|min:0',
            'effective_start' => 'required|date_format:d/m/Y',
            'min_km' => 'required|numeric|min:0',
            'max_km' => 'required|numeric|gt:min_km'
        ], [
            'max_km.gt' => 'Batas maksimal kilometer harus lebih besar dari batas minimal.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->get('current_user');
        $effectiveStart = Carbon::createFromFormat('d/m/Y', $request->effective_start)->format('Y-m-d');

        DB::beginTransaction();
        try {
            // Deactivate current active settings
            TransportAllowanceSetting::where('is_active', true)->update(['is_active' => false]);

            // Create new setting
            $setting = TransportAllowanceSetting::create([
                'base_fare' => $request->base_fare,
                'effective_start' => $effectiveStart,
                'min_km' => $request->min_km,
                'max_km' => $request->max_km,
                'is_active' => true,
                'created_by' => $user?->id
            ]);

            activity_log('setting_tunjangan', 'update', 'Memperbarui tarif dasar tunjangan transport: Rp ' . number_format($request->base_fare) . '/km', $user, $setting);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan tunjangan berhasil disimpan',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get transport allowance calculation periods list.
     */
    public function getPeriods(Request $request)
    {
        $year = $request->query('year', now()->year);

        $periods = TransportAllowancePeriod::with('calculator')
            ->where('period_year', $year)
            ->orderBy('period_month', 'asc')
            ->get();

        // Ensure all 12 months are listed (even if not calculated yet)
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $results = [];
        foreach ($months as $mNum => $mName) {
            $period = $periods->firstWhere('period_month', $mNum);
            if ($period) {
                $results[] = [
                    'id' => $period->id,
                    'month_num' => $mNum,
                    'month_name' => $mName,
                    'year' => $period->period_year,
                    'total_recipients' => $period->total_recipients,
                    'total_amount' => (float) $period->total_amount,
                    'status' => $period->status,
                    'calculated_at' => $period->calculated_at ? $period->calculated_at->format('d/m/Y H:i') : '-',
                    'calculator_name' => $period->calculator ? $period->calculator->name : '-'
                ];
            } else {
                $results[] = [
                    'id' => null,
                    'month_num' => $mNum,
                    'month_name' => $mName,
                    'year' => $year,
                    'total_recipients' => 0,
                    'total_amount' => 0.00,
                    'status' => 'draft',
                    'calculated_at' => '-',
                    'calculator_name' => '-'
                ];
            }
        }

        return response()->json([
            'year' => $year,
            'periods' => $results
        ]);
    }

    /**
     * Get calculation details (recipients list) for a specific period.
     */
    public function getPeriodDetails($id)
    {
        $period = TransportAllowancePeriod::find($id);
        if (!$period) {
            return response()->json(['message' => 'Periode tidak ditemukan'], 404);
        }

        $details = TransportAllowanceDetail::with(['employee.position', 'employee.department'])
            ->where('transport_allowance_period_id', $id)
            ->get();

        $results = $details->map(function($d, $index) {
            return [
                'no' => $index + 1,
                'employee_name' => $d->employee ? $d->employee->name : '-',
                'nip' => $d->employee ? $d->employee->nip : '-',
                'position' => $d->employee && $d->employee->position ? $d->employee->position->name : '-',
                'department' => $d->employee && $d->employee->department ? $d->employee->department->name : '-',
                'employment_type' => $d->employee ? strtoupper($d->employee->employment_type) : '-',
                'original_km' => (float) $d->original_km,
                'rounded_km' => $d->rounded_km,
                'attendance_days' => $d->attendance_days,
                'base_fare' => (float) $d->base_fare,
                'nominal' => (float) $d->nominal,
                'eligibility_status' => $d->eligibility_status,
                'calculation_note' => $d->calculation_note
            ];
        });

        return response()->json([
            'period' => [
                'id' => $period->id,
                'year' => $period->period_year,
                'month' => $period->period_month,
                'total_recipients' => $period->total_recipients,
                'total_amount' => (float) $period->total_amount,
                'status' => $period->status
            ],
            'details' => $results
        ]);
    }

    /**
     * Trigger transport allowance calculation for a month/year.
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_year' => 'required|integer',
            'period_month' => 'required|integer|min:1|max:12'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $year = $request->period_year;
        $month = $request->period_month;
        $user = $request->get('current_user');

        // Check if period exists and is locked
        $period = TransportAllowancePeriod::where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($period && $period->status === 'locked') {
            return response()->json(['message' => 'Perhitungan tunjangan untuk periode ini telah dikunci.'], 403);
        }

        // Fetch active setting
        $setting = TransportAllowanceSetting::where('is_active', true)->first();
        $baseFare = $setting ? $setting->base_fare : 5000.00;
        $minKm = $setting ? $setting->min_km : 5.00;
        $maxKm = $setting ? $setting->max_km : 25.00;

        // Create or find period record
        if (!$period) {
            $period = TransportAllowancePeriod::create([
                'period_year' => $year,
                'period_month' => $month,
                'status' => 'calculated',
            ]);
        }

        // Clear existing details for this period
        TransportAllowanceDetail::where('transport_allowance_period_id', $period->id)->delete();

        // Get all active employees
        $employees = Employee::where('status', 'active')->get();

        $totalRecipients = 0;
        $totalAmount = 0.00;

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                $originalKm = $employee->distance_km;
                $roundedKm = 0;
                $attendanceDays = 0;
                $nominal = 0.00;
                $eligibilityStatus = 'eligible';
                $note = '';

                // Rule 1: Check Employment Type (Must be Pegawai Tetap: PKWTT)
                if ($employee->employment_type !== 'pkwtt') {
                    $eligibilityStatus = 'ineligible_employment_type';
                    $note = 'Bukan pegawai tetap (' . strtoupper($employee->employment_type) . ')';
                } else {
                    // Fetch attendance summary for this period
                    $summary = AttendanceSummary::where('employee_id', $employee->id)
                        ->where('period_year', $year)
                        ->where('period_month', $month)
                        ->first();

                    $attendanceDays = $summary ? (float) $summary->hadir : 0.0;

                    // Rule 2: Minimum presence requirement (19 days)
                    if ($attendanceDays < 19.0) {
                        $eligibilityStatus = 'ineligible_presence';
                        $note = 'Kehadiran masuk kerja kurang dari 19 hari (hanya ' . $attendanceDays . ' hari)';
                    } else {
                        // Rule 3: Jarak rumah-kantor (5km - 25km)
                        if ($originalKm <= $minKm) {
                            $eligibilityStatus = 'ineligible_distance';
                            $note = 'Jarak rumah-kantor ' . $originalKm . ' km (minimal ' . (int)$minKm . ' km)';
                        } else {
                            // Cap distance at max_km
                            $cappedKm = min($originalKm, $maxKm);
                            
                            // Rounding rule: decimal < 0.5 round down, decimal >= 0.5 round up
                            // Using standard HALF_UP rounding
                            $roundedKm = (int) round($cappedKm, 0, PHP_ROUND_HALF_UP);

                            // Re-evaluate if rounded km is below minimum
                            if ($roundedKm <= (int)$minKm) {
                                $eligibilityStatus = 'ineligible_distance';
                                $note = 'Jarak setelah pembulatan adalah ' . $roundedKm . ' km (minimal ' . (int)$minKm . ' km)';
                            } else {
                                // Calculate nominal
                                $nominal = $baseFare * $roundedKm * $attendanceDays;
                                $eligibilityStatus = 'eligible';
                                $note = 'Memenuhi syarat. Kalkulasi: Rp ' . number_format($baseFare) . ' x ' . $roundedKm . ' km x ' . $attendanceDays . ' hari';
                                
                                $totalRecipients++;
                                $totalAmount += $nominal;
                            }
                        }
                    }
                }

                // Save detail
                TransportAllowanceDetail::create([
                    'transport_allowance_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'base_fare' => $baseFare,
                    'original_km' => $originalKm,
                    'rounded_km' => $roundedKm,
                    'attendance_days' => $attendanceDays,
                    'nominal' => $nominal,
                    'eligibility_status' => $eligibilityStatus,
                    'calculation_note' => $note
                ]);
            }

            // Update period summary
            $period->update([
                'total_recipients' => $totalRecipients,
                'total_amount' => $totalAmount,
                'status' => 'calculated',
                'calculated_by' => $user?->id,
                'calculated_at' => now()
            ]);

            activity_log('tunjangan_transport', 'create', 'Melakukan kalkulasi tunjangan transport periode ' . $month . '/' . $year . '. Total penerima: ' . $totalRecipients . ', Total nominal: Rp ' . number_format($totalAmount), $user, $period);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Kalkulasi tunjangan transport berhasil dijalankan.',
                'data' => [
                    'period_id' => $period->id,
                    'total_recipients' => $totalRecipients,
                    'total_amount' => $totalAmount
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan kalkulasi: ' . $e->getMessage()], 500);
        }
    }
}
