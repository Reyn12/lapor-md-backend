<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Akun Warga
        User::create([
            'nama' => 'Budi Santoso',
            'email' => 'warga@lapor.com',
            'password' => Hash::make('password123'),
            'no_telepon' => '081234567890',
            'alamat' => 'Jl. Merdeka No. 123, RT 01/RW 02, Kelurahan Sukamaju',
            'role' => 'warga',
            'foto_profil' => null,
        ]);

        // Akun Pegawai
        User::create([
            'nama' => 'Siti Aminah',
            'email' => 'pegawai@lapor.com', 
            'password' => Hash::make('password123'),
            'no_telepon' => '081234567891',
            'alamat' => 'Jl. Sudirman No. 456, RT 03/RW 04, Kelurahan Sejahtera',
            'role' => 'pegawai',
            'foto_profil' => null,
        ]);

        // Akun Kepala Kantor
        User::create([
            'nama' => 'Drs. Ahmad Fauzi, M.Si',
            'email' => 'kepala@lapor.com',
            'password' => Hash::make('password123'),
            'no_telepon' => '081234567892', 
            'alamat' => 'Jl. Gatot Subroto No. 789, RT 05/RW 06, Kelurahan Makmur',
            'role' => 'kepala_kantor',
            'foto_profil' => null,
        ]);

        echo "✅ 3 user accounts created successfully!\n";
        echo "🔑 Default password for all accounts: password123\n";
    }
}
