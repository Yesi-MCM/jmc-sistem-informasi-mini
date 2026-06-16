<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $query;
    protected $rowNumber = 0;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->query->get();
    }

    /**
     * @var Employee $employee
     */
    public function map($employee): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $employee->nip,
            $employee->name,
            $employee->email,
            $employee->phone,
            $employee->position ? $employee->position->name : '-',
            $employee->department ? $employee->department->name : '-',
            $employee->joined_at ? $employee->joined_at->format('d/m/Y') : '-',
            $employee->work_tenure,
            strtoupper($employee->employment_type),
            $employee->status === 'active' ? 'Aktif' : 'Nonaktif',
        ];
    }

    public function headings(): array
    {
        return [
            'No. Urut',
            'NIP',
            'Nama Pegawai',
            'Email',
            'Nomor HP',
            'Jabatan',
            'Departemen',
            'Tanggal Masuk',
            'Masa Kerja',
            'Status Kontrak',
            'Status',
        ];
    }
}
