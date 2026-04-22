<?php

namespace Database\Seeders;

use App\Models\DataRow;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@local.test'],
            ['name' => 'Administrator', 'password' => 'admin123', 'role' => 'admin']
        );
        User::firstOrCreate(
            ['email' => 'user@local.test'],
            ['name' => 'Pengguna Demo', 'password' => 'user1234', 'role' => 'user']
        );

        // Dataset 1 — Biaya Iklan vs Penjualan
        if (!Dataset::where('name', 'Pengaruh Biaya Iklan terhadap Penjualan')->exists()) {
            $ds = Dataset::create([
                'name'        => 'Pengaruh Biaya Iklan terhadap Penjualan',
                'case_label'  => 'Studi Kasus UMKM Kuliner',
                'description' => 'Memprediksi total penjualan bulanan berdasarkan anggaran biaya iklan yang dikeluarkan.',
                'x_label'     => 'Biaya Iklan',
                'x_unit'      => 'juta Rp',
                'y_label'     => 'Penjualan',
                'y_unit'      => 'juta Rp',
                'user_id'     => $admin->id,
                'is_default'  => true,
            ]);
            $rows = [
                ['Bulan Jan',  1.0, 12.0],
                ['Bulan Feb',  1.5, 14.5],
                ['Bulan Mar',  2.0, 17.0],
                ['Bulan Apr',  2.5, 19.5],
                ['Bulan Mei',  3.0, 23.0],
                ['Bulan Jun',  3.5, 25.5],
                ['Bulan Jul',  4.0, 29.0],
                ['Bulan Agu',  4.5, 31.5],
                ['Bulan Sep',  5.0, 34.0],
                ['Bulan Okt',  5.5, 37.5],
            ];
            foreach ($rows as [$lbl, $x, $y]) {
                DataRow::create([
                    'dataset_id' => $ds->id, 'label' => $lbl,
                    'x_value' => $x, 'y_value' => $y,
                ]);
            }
        }

        // Dataset 2 — Jam belajar vs Nilai ujian
        if (!Dataset::where('name', 'Hubungan Jam Belajar dan Nilai Ujian')->exists()) {
            $ds = Dataset::create([
                'name'        => 'Hubungan Jam Belajar dan Nilai Ujian',
                'case_label'  => 'Studi Kasus Mahasiswa',
                'description' => 'Apakah jam belajar harian mempengaruhi nilai ujian mahasiswa?',
                'x_label'     => 'Jam Belajar',
                'x_unit'      => 'jam/hari',
                'y_label'     => 'Nilai Ujian',
                'y_unit'      => 'skor',
                'user_id'     => $admin->id,
            ]);
            $rows = [
                ['Mhs A', 1.0, 55],
                ['Mhs B', 1.5, 60],
                ['Mhs C', 2.0, 65],
                ['Mhs D', 2.5, 68],
                ['Mhs E', 3.0, 72],
                ['Mhs F', 3.5, 75],
                ['Mhs G', 4.0, 80],
                ['Mhs H', 4.5, 83],
                ['Mhs I', 5.0, 88],
                ['Mhs J', 5.5, 91],
                ['Mhs K', 6.0, 95],
            ];
            foreach ($rows as [$lbl, $x, $y]) {
                DataRow::create([
                    'dataset_id' => $ds->id, 'label' => $lbl,
                    'x_value' => $x, 'y_value' => $y,
                ]);
            }
        }
    }
}
