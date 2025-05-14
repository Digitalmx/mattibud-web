<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = [
            [
                'name' => 'Oslo Øst',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Strømsveien 196, Alnabru',
                'city' => 'Oslo',
                'latitude' => 59.9500,
                'longitude' => 10.7300,
            ],
            [
                'name' => 'Oslo Vest',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Hoffsveien 10, Skøyen',
                'city' => 'Oslo',
                'latitude' => 59.9160,
                'longitude' => 10.7100,
            ],
            [
                'name' => 'Oslo Sør',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Mortensrudveien 3, Mortensrud',
                'city' => 'Oslo',
                'latitude' => 59.8511,
                'longitude' => 10.8176,
            ],
            [
                'name' => 'Oslo Sentrum',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Karl Johans gate 3',
                'city' => 'Oslo',
                'latitude' => 59.9115,
                'longitude' => 10.7579,
            ],
            [
                'name' => 'Asker og Bærum',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Sandviksveien 184',
                'city' => 'Sandvika',
                'latitude' => 59.8313,
                'longitude' => 10.4176,
            ],
            [
                'name' => 'Nedre Romerike',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Strømsvegen 55',
                'city' => 'Lillestrøm',
                'latitude' => 59.9551,
                'longitude' => 11.0379,
            ],
            [
                'name' => 'Øvre Romerike',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Jessheim Storsenter',
                'city' => 'Jessheim',
                'latitude' => 59.9800,
                'longitude' => 10.9500,
            ],
            [
                'name' => 'Follo',
                'logo_path' => null,
                'pdf_path' => null,
                'address' => 'Ski Storsenter',
                'city' => 'Ski',
                'latitude' => 59.7178,
                'longitude' => 10.8367,
            ],
        ];

        foreach ($stores as $storeData) {
            Store::create($storeData);
        }
    }
}
