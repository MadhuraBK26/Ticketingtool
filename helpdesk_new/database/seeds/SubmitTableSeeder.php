<?php

use Illuminate\Database\Seeder;

class SubmitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('submit_ticket')->insert([
            'submit' => 1
        ]);
    }
}
