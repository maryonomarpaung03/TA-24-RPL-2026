<?php

namespace App\Support;

class ProjectCatalog
{
    /**
     * @return array<int, array{id:int,name:string,description:string}>
     */
    public static function library(): array
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Aplikasi Absensi Online Berbasis QR Code',
                'description' => 'Eksperimen integrasi Computational Thinking dalam kurikulum SMK melalui proyek nyata pembangunan smart garden berbasis sensor IoT.',
            ],
            2 => [
                'id' => 2,
                'name' => 'Abstraksi Aljabar Digital',
                'description' => 'Penerapan konsep abstraksi CT untuk mempermudah pemahaman konsep aljabar dalam visualisasi.',
            ],
            3 => [
                'id' => 3,
                'name' => 'Visualisasi Algoritma Kota',
                'description' => 'Proyek kolaborasi teknik sipil dan informatika untuk memvisualisasikan jalur optimal kota.',
            ],
            4 => [
                'id' => 4,
                'name' => 'Robotika Berbasis Pola',
                'description' => 'Analisis pengenalan pola gerakan motorik pada robot edukasi berbasis CT.',
            ],
        ];
    }

    public static function find(int|string|null $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $key = (int) $id;
        $lib = self::library();

        return $lib[$key] ?? null;
    }

    public static function name(int|string $id): string
    {
        $row = self::find($id);

        return $row['name'] ?? 'Projek Tidak Ditemukan';
    }
}
