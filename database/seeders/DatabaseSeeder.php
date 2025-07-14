<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder dalam urutan yang benar
        $this->call([
            UserSeeder::class,          // Users dulu (karena ada foreign key ke users)
            KategoriSeeder::class,      // Kategori kedua (karena ada foreign key ke kategori)
            PengaduanSeeder::class,     // Pengaduan ketiga (karena depend ke users & kategori)
            NotifikasiSeeder::class,    // Notifikasi keempat (karena depend ke users & pengaduan)
            LaporanSeeder::class,       // Laporan terakhir (karena depend ke users)
        ]);

        echo "\n🎉 Database seeding completed successfully!\n";
        echo "📝 Data yang telah dibuat:\n";
        echo "   - 3 Users (warga, pegawai, kepala_kantor)\n";
        echo "   - 10 Kategori pengaduan\n";
        echo "   - 5 Sample pengaduan\n";
        echo "   - Status history untuk setiap pengaduan\n";
        echo "   - 8 Notifikasi untuk berbagai user\n";
        echo "   - 7 Laporan (harian, mingguan, bulanan, tahunan)\n";

        // Comment yang lama biar gak conflict
        // User::factory(10)->create();
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
