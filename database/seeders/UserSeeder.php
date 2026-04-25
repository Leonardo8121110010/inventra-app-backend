<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Remove all non-admin users
        User::where('email', '!=', 'root@root.com')->delete();

        $admin = User::updateOrCreate(
            ['email' => 'root@root.com'],
            [
                'name'      => 'Leonardo Carrillo',
                'password'  => Hash::make('root'),
                'role'      => 'admin',
                'branch_id' => '00-castillo',
                'avatar'    => 'LC',
            ]
        );

        // Assign ALL branches to admin
        $allBranchIds = Branch::pluck('id')->toArray();
        $syncData = [];
        foreach ($allBranchIds as $branchId) {
            $syncData[$branchId] = ['is_primary' => $branchId === '00-castillo'];
        }
        $admin->branches()->sync($syncData);
    }
}
