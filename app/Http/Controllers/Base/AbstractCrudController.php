<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

abstract class AbstractCrudController extends Controller
{
	protected string $pattern;
	protected string $permission;
	protected string $model;
	protected array $indexSetup;
	protected array $dialogSetup;
	protected bool $export = false;
	protected bool $create = true;
	protected bool $createData = false;
	protected bool $pdf = false;
	protected bool $clone = false;
	protected bool $magic = false;
	protected bool $activeYear = false;
	protected bool $recordsYear = false;
	
	protected array $verifyDestroy = [];  // Relazioni da verificare prima di cancellare
	
	protected array $exportSetup;
	
	protected string $root = '/resources/js/Pages/';  // Root delle componenti Inertia

	/**
	 * Mostra la lista delle risorse in formato Inertia.
	 *
	 * @return \Inertia\Response
	**/
	public function index()
	{
		$collection = $this->getCollectionIndex();
		
		$props = $this->getPropsIndex($collection);
	

		return Inertia::render($this->getComponentIndex(), $props);
	}

	/**
	 * Mostra il form per creare una nuova risorsa.
	 *
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function create()
    {
		return $this->getData('create');
    }
	
	/**
	 * Mostra una singola risorsa per visualizzazione.
	 *
	 * @param int $id  ID della risorsa da mostrare
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function show($id)
    {
        $object = $this->resolveModel($id);
        return $this->getData('show', $object);
    }

	/**
	 * Mostra il form di modifica per una risorsa esistente.
	 *
	 * @param int $id  ID della risorsa da modificare
	 * @return \Illuminate\Http\JsonResponse
	**/
    public function edit($id)
    {
        $object = $this->resolveModel($id);
        return $this->getData('edit', $object);
    }
	
	/**
	 * Salva una nuova risorsa nel database.
	 *
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function store()
	{
		return DB::transaction(function () {
			$validatedData = $this->validationData('store');

			$this->beforeStore($validatedData);
			$object = $this->model::create($validatedData);
			$this->afterStore($object, $validatedData);

			return response()->json(['record' => $this->getJsonData('store', $object)]);
		});
	}
	
	/**
	 * Aggiorna una risorsa esistente.
	 *
	 * @param int $id  ID della risorsa da aggiornare
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function update($id)
    {
		return DB::transaction(function () use ($id) {
			$object = $this->resolveModel($id);
			$validatedData = $this->validationData('update', $object);
			
			$this->beforeUpdate($validatedData);
			$object->update($validatedData);
			$this->afterUpdate($object, $validatedData);
			
			return response()->json(['record' => $this->getJsonData('update', $object)]);
		});
    }
	
	/**
	 * Elimina una risorsa dal database.
	 *
	 * @param int $id  ID della risorsa da eliminare
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function destroy($id)
    {
		return DB::transaction(function () use ($id) {
			$object = $this->resolveModel($id);
			$data = $this->getJsonData('destroy', $object);

			$object->delete();

			return response()->json(['record' => $data]);
		});
    }
	
	/**
	 * Duplica una risorsa esistente.
	 *
	 * @param int $id  ID della risorsa da clonare
	 * @return \Illuminate\Http\JsonResponse
	**/
	public function clone($id)
	{
		return DB::transaction(function () use ($id) {
			$object = $this->resolveModel($id);
			$data = $this->getJsonData('clone', $object);
			
			$newObject = $this->setClone($object);
			
			$data = $this->getJsonData('clone', $newObject);
			
			return response()->json(['record' => $data]);
		});
	}
	
	/**
	 * Esporta i dati in formato Excel per una determinata risorsa.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $id  ID della risorsa da esportare
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	**/
	public function export(Request $request, $id)
	{
		$year = $request->query('year', date('Y'));

		$object = $this->resolveModel($id);
		
		$export_class = $this->exportSetup['class'];
		$name_file = array_key_exists('file', $this->exportSetup) ? $this->exportSetup['file'] : $this->pattern;
        $export_file = $name_file  . '-' . $year;
		
        return Excel::download(new $export_class($object, $year), $export_file . '.xlsx');
	}
	
	public function getPattern()
	{
		return $this->pattern;
	}

	protected function setPermissionRole()
	{
		return false;
	}
	
	protected function getPropsIndex(
		Collection $collection, 
		array $actionPermissions = [
			'show' => 'show', 
			'edit' => 'edit', 
			'update' => 'edit', 
			'destroy' => 'delete'
		],
		bool $create = true,
		bool $export = false
	)
	{
		$permissions = $this->setPermissionRole();

		if($permissions) {
			[$actionPermissions, $create_active, $filter, $export] = $permissions;
			$this->export = $export;
		} else {
			$create_active = $create === false ? $create : $this->create;
			
			$filter = request()->query('filter', null);
			if($this->pdf === true) $actionPermissions['pdf'] = 'pdf';
			if($this->clone === true) $actionPermissions['clone'] = 'clone';
			$this->export = $export;
		}
		
		return [
			'title' => $this->indexSetup['plural'],
			'single' => $this->indexSetup['single'],
			'type' => $this->indexSetup['type'],
			'icon' => $this->indexSetup['icon'],
			'itemsPerPage' => $this->indexSetup['itemsPerPage'] ?? 20,
			'order' => $this->indexSetup['order'] ?? [ 'key' => 'nome', 'order' => 'asc' ],
			'nameDialog' => $this->indexSetup['nameDialog'] ?? 'nome',
			'filter' => $filter,
			'activeYear' => $this->activeYear,
			'recordsYear' => $this->recordsYear,
			'headers' => $this->indexSetup['headers'],
			'data' => $this->getDataIndex($collection, $actionPermissions),
			'new' => $create_active ? $this->getAction() : null,
			'export' => $this->export ? $this->getAction(type: 'export') : null,
			'dialogSetup' => $this->dialogSetup ?? ['create' => null, 'edit' => null, 'show' => null],
			'components' => $this->getComponentsDialog()
		];
	}
	
	/**
	 * Costruisce la struttura dati da passare alla tabella index.
	 *
	 * @param \Illuminate\Database\Eloquent\Collection $collection
	 * @param array $actionPermissions
	 * @return \Illuminate\Support\Collection
	**/
	protected function getDataIndex(Collection $collection, array $actionPermissions)
	{
		return $collection->map(function ($element) use ($actionPermissions) {
			return array_merge(
				$this->getJsonData('index', $element),
				['actions' => $this->getAction($element, $actionPermissions)]
			);
		});
	}
	
	protected function setComponents()
	{
		$content = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($this->pattern))));
		
		return [
			'index' => 'Crud/CrudIndex',
			'create' => 'Crud/CrudCreate',
			'show' => 'Crud/CrudShow',
			'edit' => 'Crud/CrudEdit',
			'content' => $content . 'Content'
		];
	}
	
	/**
	 * Restituisce la collezione di record da mostrare nella tabella index.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	**/
	protected function getCollectionIndex() 
	{
		return $this->model::all();
	}
		
	protected function setOtherData(string $type, Model $object) 
	{
		return [];
	}
	
	protected function setValidation(Model $object)
	{
		return [];
	}
	
	protected function setJsonData(string $type, Model|Collection $object)
	{
		return [];
	}
	
	protected function setClone(Model $object)
	{
		$newDocument = $object->replicate();
		
		return $newDocument;
	}
	
	protected function beforeStore(&$validatedData) {}
	protected function afterStore(&$object, $validatedData) {}
	
	protected function beforeUpdate(&$validatedData) {}
	protected function afterUpdate(&$object, $validatedData) {}
	
	/**
	 * Restituisce l'array delle rotte/azioni disponibili per un oggetto o per tipo.
	 *
	 * @param \Illuminate\Database\Eloquent\Model|null $object
	 * @param array $actionPermissions
	 * @param string|null $type
	 * @param string|null $url
	 * @return array
	**/
	protected function getAction(
		Model $object = null, 
		array $actionPermissions = [
			'show' => 'show', 
			'edit' => 'edit', 
			'update' => 'edit', 
			'destroy' => 'delete'
		], 
		string $type = null,
		string $url = null
	) {
		$user = auth()->user();
		$pattern = $url ?? $this->pattern;
		$permission = $this->permission ?? $this->pattern;

		if($object) {
			$actions = collect($actionPermissions)->mapWithKeys(function ($permissionAction, $routeAction) use ($user, $pattern, $permission, $object) {
				if ($permissionAction === null) {
					return [$routeAction => null];
				}

				$route = [
					$routeAction => $user->can("{$permission}.{$permissionAction}") 
						? route("{$pattern}.{$routeAction}", $object) 
						: false
				];

				if ($permissionAction == 'delete' && is_array($this->verifyDestroy) && count($this->verifyDestroy) > 0) {
					foreach ($this->verifyDestroy as $relation) {
						if (!$this->verifyRelationDestroy($object, $relation)) {
							$route = [$routeAction => false];
							break;
						}
					}
				}

				return $route;
			})->toArray();

			// Aggiungi azione QR code solo per ordini vendita
			// Il pattern indica il tipo di entità (es: 'ordini-vendita', 'ordini-acquisto')
			if (in_array($pattern, ['ordini-vendita'])) {
				$actions['qr'] = true;
			} else {
				$actions['qr'] = false; // Nasconde il pulsante per altre entità
			}

			return $actions;
		} 
		
		if($type != null) return $user->can($pattern . '.' . $type) ? route($pattern . '.' . $type, false): false;

		$defaultActions = ['create' => 'create', 'store' => 'create'];
		return collect($defaultActions)->mapWithKeys(function ($action, $routeAction) use ($user, $pattern, $permission) {
			return [
				$routeAction => $user->can("{$permission}.{$action}") 
					? route("{$pattern}.{$routeAction}") 
					: false
			];
		})->toArray();
	}
	
	/**
	 * Restituisce i dati da serializzare in JSON per le viste.
	 *
	 * @param string $type
	 * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null $object
	 * @return array|null
	**/
	protected function getJsonData(string $type, Model|Collection $object = null)
	{
		$object = $object ?? new $this->model;
		 
		 if ($this->createData || $type !== 'create') {
			$jsonData = $this->setJsonData($type, $object) ?? [];
		} else {
			$jsonData = [];
		}
        
        $main = $jsonData['main'] ?? [];
        
		$data = $jsonData[$type] ?? [];
		
		if ($object->exists && in_array($type, ['store', 'update', 'clone'])) {
			$data['actions'] = $this->getAction($object);
		}

        return (!empty($main) || !empty($data)) ? array_merge((array) $main, (array) $data) : null;
	}
	
	/**
	 * Risolve e restituisce un'istanza del modello a partire dall'ID.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	**/
	protected function resolveModel($id)
    {
        return $this->model::findOrFail($id);
    }
	
	/**
	 * Restituisce il nome del componente index.
	 *
	 * @return string
	**/
	private function getComponentIndex() 
	{
		$components = $this->setComponents();
		return $components['index'];
	}
	
	/**
	 * Restituisce i path completi dei componenti usati nei dialog (create, edit, show, content).
	 *
	 * @return array
	**/
	private function getComponentsDialog() 
	{
		$components = $this->setComponents();

		return [
			'create' => isset($components['create']) ? $this->root . $components['create'] : null,
			'show' => isset($components['show']) ? $this->root . $components['show'] : null,
			'edit' => isset($components['edit']) ? $this->root . $components['edit'] : null,
			'content' => isset($components['content']) ? $this->root . $components['content'] : null,
		];
	}
	
	/**
	 * Restituisce la risposta JSON con i dati principali per una vista.
	 *
	 * @param string $type  Tipo azione (create, edit, show)
	 * @param \Illuminate\Database\Eloquent\Model|null $object
	 * @return \Illuminate\Http\JsonResponse
	**/
	private function getData(string $type, Model $object = null)
	{
		return response()->json(array_merge(
            ['resource' => $this->getJsonData($type, $object)],
            $this->getOtherData($type, $object)
        ));
	}
	
	/**
	 * Restituisce eventuali dati aggiuntivi per la vista
	 *
	 * @param string $type Tipo azione (create, edit, show)
	 * @param \Illuminate\Database\Eloquent\Model|null $object
	 * @return array
	**/
	private function getOtherData($type, Model $object = null)
	{
		return $this->setOtherData($type, $object ?? new $this->model);
	}
	
	/**
	 * Esegue la validazione dei dati della richiesta in base al tipo (store/update).
	 *
	 * @param string $type  Tipo operazione ('store' o 'update')
	 * @param \Illuminate\Database\Eloquent\Model|null $object
	 * @return array  Dati validati
	**/
	private function validationData(string $type, Model $object = null) 
	{
		$object = $object ?? new $this->model;
        $validationData = $this->setValidation($object);

        $additionalRules = $type === 'store' ? $validationData['store'] ?? [] : $validationData['update'] ?? [];
        $rules = array_merge($validationData['rules'] ?? [], $additionalRules);

        return request()->validate($rules, $validationData['messages'] ?? []);
	}

	/**
	 * Verifica se è sicuro eliminare un oggetto in base alle sue relazioni.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $object
	 * @param string $relation
	 * @return bool
	**/
	private function verifyRelationDestroy(Model $object, string $relation): bool
	{
		if (!method_exists($object, $relation)) {
			return true;
		}

		$relationData = $object->{$relation} ?? collect();

		return $relationData instanceof Collection ? $relationData->isEmpty() : true;
	}
}