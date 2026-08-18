<?php

namespace App\Http\Controllers;

use DB;

use App\Models\User;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   /* public function index(Request $request)
    {
        //return view('backend.user.main');
        //return User::select('id', 'name', 'email')->get();

        $query = User::select('*');

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%");
                });
            }

            return $query->orderBy('id', 'desc')->paginate(10);
    }
*/
    public function getUsuarios(Request $request)
    {
        $query = User::select('*');

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%");
                });
            }

            return $query->orderBy('id', 'desc')->paginate(10);
    }

    public function getUsuario($id)
    {
        $rows = collect(DB::select("SELECT * FROM users WHERE id = ?", [$id]))->first();

        return $rows;
    }

    public function getMenu()
    {
        return DB::table('menu')
        ->where('estatus', 1)
        ->orderBy('orden')
        ->get();
    }

    public function getSubMenu($pId)
    {
        return DB::table('submenu')
        ->where('idmenu', $pId)
        ->where('estatus', 1)
        ->orderBy('orden')
        ->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|unique:users,username',
            'name' => 'required',
            'password' => 'required',
        ]);

        $data['email'] = $data['username'];
        $data['password'] = bcrypt($data['password']);
        $data['contrasena'] = $data['password'];

        return User::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function guardarUsuario(Request $request, $id = null)
    {
        $rules = [
            'username' => [
                'required',
                Rule::unique('users', 'username')->ignore($id, 'id')
            ],
            'name' => 'required',
            'password' => 'required',
            'role' => 'nullable|string|in:recursos_humanos,directivo,operaciones_combustible,operaciones_mantenimiento,sistemas',
        ];

        $validated = $request->validate($rules);

        $validated['email'] = $request->username;
        $validated['password'] = bcrypt($request->password);
        $validated['contrasena'] = $request->password;

        $roleService = app(RoleService::class);

        if ($id) {            
            // 🔄 UPDATE
            $currentRole = DB::table('users')->where('id', $id)->value('role');

            DB::table('users')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            // Si el rol cambió, se re-aplican los permisos del rol automáticamente
            if (!empty($validated['role']) && $validated['role'] !== $currentRole) {
                $roleService->applyRoleToUser((int) $id, $validated['role']);
            }

            return response()->json([
                'message' => 'Usuario actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $userId = DB::table('users')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Al crear, se otorgan los permisos del rol automáticamente
        if (!empty($validated['role'])) {
            $roleService->applyRoleToUser((int) $userId, $validated['role']);
        }

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'id' => $userId
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function buildMenuForUser()
    {
        $menu = [[
            'title' => 'Menu',
            'items' => collect($this->getMenu())->map(function ($item) {

                $menuItem = [
                    'name'     => $item->nombre,
                    'path'     => $item->controlador,
                    'iconName' => $item->icono,
                ];

                $subitems = $this->getSubMenu($item->id);

                if ($subitems->isNotEmpty()) {
                    $menuItem['subItems'] = $subitems->map(fn ($subitem) => [
                        'name' => $subitem->nombre,
                        'path' => $subitem->link,
                    ])->values();
                }

                return $menuItem;
            })->values()
        ]];

        return $menu;

        /*$menu = [];

        // Menu base (todos)
        $menu[] = [
            'title' => 'Menu',
            'items' => [
                [
                    'name' => 'Dashboard',
                    'path' => '/',
                    'iconName' => 'HomeIcon'
                ],
                [
                    'name' => 'Sistemas',
                    'path' => '/',
                    'iconName' => 'GridIcon',
                    'subItems' => [
                        ['name' => 'Usuarios', 'path' => '/sistemas/usuarios']
                    ]
                ],
                [
                    'name' => 'Recursos Humanos',
                    'path' => '/',
                    'iconName' => 'UserGroupIcon',
                    'subItems' => [
                        ['name' => 'Empleados', 'path' => '/recursos-humanos/empleados']
                    ]
                ]
            ]
        ];*/

        return $menu;
    }

    public function show_permissions($userId)
    {
        $permissions = DB::table('user_permissions')
            ->where('user_id', $userId)
            ->orderBy('permission_path')
            ->pluck('permission_path');

        return response()->json([
            'permissions' => $permissions
        ]);
    }

    public function update_permissions(Request $request, $userId)
    {
        // 1️⃣ Validación
        $validated = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $userId) {

            // 2️⃣ Borrar permisos actuales
            DB::table('user_permissions')
                ->where('user_id', $userId)
                ->delete();

            // 3️⃣ Insertar nuevos permisos
            $data = collect($validated['permissions'])
                ->unique()
                ->map(function ($path) use ($userId) {
                    return [
                        'user_id'         => $userId,
                        'permission_path'=> $path,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                })
                ->values()
                ->toArray();

            if (!empty($data)) {
                DB::table('user_permissions')->insert($data);
            }
        });

        return response()->json([
            'message' => 'Permisos actualizados correctamente'
        ]);
    }

    // Obtener sucursales de combustible asignadas a un usuario
    public function getUserSucursalCombustible($userId)
    {
        $rows = DB::table('usuarios_sucursal_combustible as usc')
            ->leftJoin('sucursales as s', 'usc.idsucursal', '=', 's.id')
            ->select('usc.idsucursal as id', 's.nombre')
            ->where('usc.idusuario', $userId)
            ->get();

        return response()->json(['data' => $rows]);
    }

    // Obtener sucursales de combustible del usuario autenticado
    public function getCurrentUserSucursalCombustible(Request $request)
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('usuarios_sucursal_combustible as usc')
            ->leftJoin('sucursales as s', 'usc.idsucursal', '=', 's.id')
            ->select('usc.idsucursal as id', 's.nombre')
            ->where('usc.idusuario', $userId)
            ->get();

        return response()->json(['data' => $rows]);
    }

    // Guardar sucursales de combustible para un usuario
    public function saveUserSucursalCombustible(Request $request, $userId)
    {
        $request->validate([
            'sucursales' => 'required',
        ]);

        $sucursales = $request->sucursales;

        if (is_string($sucursales)) {
            $sucursales = array_filter(array_map('trim', explode(',', $sucursales)));
        }

        if (!is_array($sucursales)) {
            $sucursales = [$sucursales];
        }

        DB::table('usuarios_sucursal_combustible')
            ->where('idusuario', $userId)
            ->delete();

        $data = collect($sucursales)
            ->unique()
            ->map(fn($idsucursal) => [
                'idusuario'  => $userId,
                'idsucursal' => $idsucursal,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->toArray();

        if (!empty($data)) {
            DB::table('usuarios_sucursal_combustible')->insert($data);
        }

        return response()->json(['message' => 'Configuración guardada correctamente']);
    }
}
