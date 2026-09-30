<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts, all with the password "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin LaraDesk',
            'email' => 'admin@laradesk.test',
        ]);

        foreach (range(1, 3) as $i) {
            User::factory()->agent()->create([
                'email' => "agent{$i}@laradesk.test",
            ]);
        }

        User::factory()->client()->create([
            'name' => 'Client Démo',
            'email' => 'client@laradesk.test',
        ]);

        User::factory()->client()->count(9)->create();
    }
}
