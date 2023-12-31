<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

    foreach (range(1, 5) as $index) {
        DB::table('users')->insert([
            'name' => $faker->name,
            'email' => $faker->safeEmail,
            'premium' => false,
            'free_generated' => 0,
            'premium_start' => now(),
            'premium_end' => now(),
            'password' => Hash::make('password'), // You can set a default password
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    }
}
