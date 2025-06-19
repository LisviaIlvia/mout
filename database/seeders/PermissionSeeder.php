<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
	/* php artisan db:seed --class=PermissionSeeder
	   php artisan cache:clear
       php artisan optimize
       php artisan clear-compiled
	*/
	
     public function run()
    {
        $entities = [
		    //'utenti',
			//'ruoli',
			//'azienda',
			//'clienti',
			//'fornitori',
			//'aliquote-iva',
			//'metodi-pagamento',
			//'conti-bancari',
			//'spedizioni',
			//'merci',
			//'categorie',
			'ordini-vendita',
			//'ddt-entrata',
			//'ddt-uscita',
			//'fatture-acquisto',
			//'fatture-vendita',
			//'fatture-proforma',
			'ordini-acquisto',
			//'note-credito-attive',
			//'note-credito-passive'
			//'attivita'
        ];

		$actions = ['show', 'create', 'edit', 'delete'];
		
		/*$permissions[] = ['name' => "ddt-uscita.clone"];
		$permissions[] = ['name' => "fatture-vendita.clone"];
		$permissions[] = ['name' => "ordini-vendita.clone"];
		$permissions[] = ['name' => "note-credito-attive.clone"];
		$permissions[] = ['name' => "fatture-proforma.clone"];*/
		
	    //$actions = ['pdf', 'magic'];

		$permissions[] = ['name' => "ordini-vendita.pdf"];
		$permissions[] = ['name' => "ordini-acquisto.pdf"];
		
		$permissions[] = ['name' => "magazzino.show"];
		$permissions[] = ['name' => "magazzino.export"];

		foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $permissions[] = ['name' => "{$entity}.{$action}"];
            }
        }
		
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}