<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Pegawai</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            color: #0d6efd;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 11px;
        }
        .employee-table {
            width: 100%;
            border-collapse: collapse;
        }
        .employee-table th, .employee-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            text-align: left;
        }
        .employee-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .employee-table tr:nth-child(even) {
            background-color: #fafbfc;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
        }
        .badge-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-inactive {
            background-color: #f8d7da;
            color: #842029;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>DAFTAR DIREKTORI PEGAWAI JMC</h2>
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }} | Total Pegawai: {{ $employees->count() }}</p>
    </div>

    <table class="employee-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 80px;">NIP</th>
                <th>Nama Pegawai</th>
                <th>Jabatan</th>
                <th>Departemen</th>
                <th style="width: 70px;">Tgl Masuk</th>
                <th style="width: 90px;">Masa Kerja</th>
                <th style="width: 60px;">Kontrak</th>
                <th style="width: 50px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $employee->nip }}</strong></td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->position ? $employee->position->name : '-' }}</td>
                    <td>{{ $employee->department ? $employee->department->name : '-' }}</td>
                    <td>{{ $employee->joined_at ? $employee->joined_at->format('d/m/Y') : '-' }}</td>
                    <td>{{ $employee->work_tenure }}</td>
                    <td>{{ strtoupper($employee->employment_type) }}</td>
                    <td>
                        @if($employee->status === 'active')
                            <span class="badge badge-active">Aktif</span>
                        @else
                            <span class="badge badge-inactive">Nonaktif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #888; padding: 20px;">Tidak ada data pegawai.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Portal Kepegawaian Mini - JMC IT Consultant - Halaman 1 dari 1
    </div>
</body>
</html>
