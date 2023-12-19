<?php

namespace Database\Seeders;

use App\Models\ClanRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ClanRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'leader' => 'Leader',
            'seatmanager' => 'Seating Manager',
            'member' => 'Member',
        ];

        foreach ($roles as $code => $name) {
            $role = ClanRole::whereCode($code)->first();
            if (!$role) {
                $role = new ClanRole();
                $role->code = $code;
            }
            $role->name = $name;
            $role->save();
            Log::info("Updated {$role}");
        }
    }
}
