<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
	/* php artisan db:seed --class=UserSeeder
	   php artisan cache:clear
       php artisan optimize
       php artisan clear-compiled
	*/
	
     public function run()
    {
       $user = User::create([
            'name' => 'Amministratore',
            'email' => 'a.moscetta@dunp.it',
            'password' => Hash::make('password'),
        ]);

        $role = Role::create(['name' => 'Amministratore']);
		
		$entities = [
			'utenti',
			'ruoli',
			'azienda'
        ];

        $actions = ['show', 'create', 'edit', 'delete'];

        $permissions = [];

		foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $permissions[] = "{$entity}.{$action}";
            }
        }
		
		$role = Role::find(1);
		
		$role->syncPermissions($permissions);

		$user->assignRole($role);
    }
}