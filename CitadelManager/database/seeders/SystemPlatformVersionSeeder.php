<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class SystemPlatformVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $platforms = factory(App\SystemPlatform::class, 7)->create();
        // seed system versions
        factory(App\SystemVersion::class, 10)->create([
            'platform_id' => function() use($platforms) {
                return $platforms->random()->id;
            },
        ]);
    }
}
