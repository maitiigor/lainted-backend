<?php

namespace Database\Seeders;

use App\Domain\Locations\Models\Lga;
use Illuminate\Database\Seeder;

class LgaSeeder extends Seeder
{
    /**
     * Starter seed covering the two markets named in the product doc
     * (#LagosBales, #AbujaThrift, #IkejaSells). Expand with the full
     * nationwide 774-LGA list before launch.
     */
    public function run(): void
    {
        $lgas = [
            'Lagos' => [
                'Agege', 'Ajeromi-Ifelodun', 'Alimosho', 'Amuwo-Odofin', 'Apapa',
                'Badagry', 'Epe', 'Eti-Osa', 'Ibeju-Lekki', 'Ifako-Ijaiye',
                'Ikeja', 'Ikorodu', 'Kosofe', 'Lagos Island', 'Lagos Mainland',
                'Mushin', 'Ojo', 'Oshodi-Isolo', 'Shomolu', 'Surulere',
            ],
            'FCT' => [
                'Abaji', 'Abuja Municipal', 'Bwari', 'Gwagwalada', 'Kuje', 'Kwali',
            ],
        ];

        foreach ($lgas as $state => $names) {
            foreach ($names as $name) {
                Lga::firstOrCreate(['state' => $state, 'name' => $name]);
            }
        }
    }
}
