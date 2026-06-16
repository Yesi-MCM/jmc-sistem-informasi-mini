<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detail Pegawai - {{ $employee->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h2 {
            margin: 0;
            color: #0d6efd;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .profile-container {
            width: 100%;
            margin-bottom: 30px;
        }
        .profile-photo {
            width: 150px;
            vertical-align: top;
        }
        .profile-photo img {
            width: 140px;
            height: 175px;
            border-radius: 6px;
            border: 1px solid #ddd;
            object-fit: cover;
        }
        .profile-info {
            vertical-align: top;
            padding-left: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 6px 4px;
            vertical-align: top;
        }
        .info-table td.label {
            font-weight: bold;
            color: #555;
            width: 140px;
        }
        .info-table td.colon {
            width: 10px;
            color: #888;
        }
        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #0d6efd;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 6px;
            margin-top: 30px;
            margin-bottom: 12px;
        }
        .education-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .education-table th, .education-table td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: left;
        }
        .education-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>PORTAL PEGAWAI JMC</h2>
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }} | Portal HRD JMC IT Consultant</p>
    </div>

    <table class="profile-container">
        <tr>
            <td class="profile-photo">
                @if($employee->photo_path && file_exists(public_path('storage/' . $employee->photo_path)))
                    <img src="{{ public_path('storage/' . $employee->photo_path) }}" alt="Foto Pegawai">
                @else
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iMTc1IiB2aWV3Qm94PSIwIDAgMTQwIDE3NSI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE0IiBmaWxsPSIjYWFhIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+Tm8gUGhvdG88L3RleHQ+PC9zdmc+" alt="Foto Pegawai">
                @endif
            </td>
            <td class="profile-info">
                <table class="info-table">
                    <tr>
                        <td class="label">NIP</td>
                        <td class="colon">:</td>
                        <td><strong>{{ $employee->nip }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="colon">:</td>
                        <td>{{ $employee->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Email</td>
                        <td class="colon">:</td>
                        <td>{{ $employee->email }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nomor HP</td>
                        <td class="colon">:</td>
                        <td>{{ $employee->phone }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td>{{ $employee->position ? $employee->position->name : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Departemen</td>
                        <td class="colon">:</td>
                        <td>{{ $employee->department ? $employee->department->name : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status Kontrak</td>
                        <td class="colon">:</td>
                        <td>{{ strtoupper($employee->employment_type) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status Kepegawaian</td>
                        <td class="colon">:</td>
                        <td>{{ $employee->status === 'active' ? 'Aktif' : 'Nonaktif' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Detail Informasi Pribadi</div>
    <table class="info-table">
        <tr>
            <td class="label">Tempat, Tanggal Lahir</td>
            <td class="colon">:</td>
            <td>{{ $employee->birth_place }}, {{ $employee->birth_date ? $employee->birth_date->format('d F Y') : '-' }} (Usia: {{ $employee->age }} Tahun)</td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="colon">:</td>
            <td>{{ ucfirst($employee->gender) }}</td>
        </tr>
        <tr>
            <td class="label">Status Pernikahan</td>
            <td class="colon">:</td>
            <td>{{ ucfirst($employee->marital_status) }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Anak</td>
            <td class="colon">:</td>
            <td>{{ $employee->children_count }} Anak</td>
        </tr>
        <tr>
            <td class="label">Jarak Rumah-Kantor</td>
            <td class="colon">:</td>
            <td>{{ $employee->distance_km }} km</td>
        </tr>
        <tr>
            <td class="label">Kecamatan</td>
            <td class="colon">:</td>
            <td>{{ $employee->district ? $employee->district->name : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kabupaten/Kota</td>
            <td class="colon">:</td>
            <td>{{ $employee->district && $employee->district->regency ? $employee->district->regency->name : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Provinsi</td>
            <td class="colon">:</td>
            <td>{{ $employee->district && $employee->district->regency && $employee->district->regency->province ? $employee->district->regency->province->name : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Lengkap</td>
            <td class="colon">:</td>
            <td>{{ $employee->full_address }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Masuk Kerja</td>
            <td class="colon">:</td>
            <td>{{ $employee->joined_at ? $employee->joined_at->format('d F Y') : '-' }} (Masa Kerja: {{ $employee->work_tenure }})</td>
        </tr>
    </table>

    <div class="section-title">Riwayat Pendidikan</div>
    <table class="education-table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Jenjang Pendidikan</th>
                <th>Nama Sekolah/Universitas</th>
                <th style="width: 100px;">Tahun Lulus</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employee->educations as $index => $edu)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $edu->education_level }}</td>
                    <td>{{ $edu->school_name }}</td>
                    <td>{{ $edu->graduation_year }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #888;">Tidak ada data riwayat pendidikan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Portal Kepegawaian Mini - JMC IT Consultant - Halaman 1 dari 1
    </div>
</body>
</html>
