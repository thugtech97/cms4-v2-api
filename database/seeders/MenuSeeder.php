<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('menus')->updateOrInsert(
            ['id' => 1], // ensures re-run safety
            [
                'name' => 'Main Menu',
                'items' => json_encode([
                    [
                        'id' => 1,
                        'label' => 'Home',
                        'children' => [],
                    ],
                    [
                        'id' => 2,
                        'label' => 'About',
                        'children' => [],
                    ],
                    [
                        'id' => 4,
                        'label' => 'News',
                        'children' => [],
                    ],
                    [
                        'id' => 5,
                        'label' => 'Contact Us',
                        'children' => [],
                    ],
                ]),
                'is_active' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ]
        );
    }
}
