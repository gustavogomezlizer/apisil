<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\FinanzasController;
use App\Http\Controllers\SincronizadorController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\CatalogosController;
use App\Http\Controllers\ControlUnidadController;
use App\Http\Controllers\RecursosHumanosController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Landing Page

//HOME

Route::get('/', function () {
    return 'API Lizer funcionando';
});

Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth');

//FINANZAS
Route::get('/finanzas/presupuesto_view', [FinanzasController::class, 'presupuesto_view'])->name('presupuesto_view')->middleware('auth');
Route::get('/finanzas/controlnc_view', [FinanzasController::class, 'controlnc_view'])->name('controlnc_view')->middleware('auth');
Route::get('/finanzas/presupuesto_gastos_view', [FinanzasController::class, 'presupuesto_gastos_view'])->name('presupuesto_gastos_view')->middleware('auth');
Route::get('/finanzas/getListadoPresupuestoGastos/{periodo}/{idsucursal}', [FinanzasController::class, 'getListadoPresupuestoGastos']);
Route::get('/presupuesto_listado', [FinanzasController::class, 'getListadoPresupuesto'])->name('presupuesto_listado');

Route::post('/finanzas/savePresupuestoGastos', [FinanzasController::class, 'savePresupuestoGastos']);

//CONTROL UNIDAD
Route::get('/controlunidad/unidades_view', [ControlUnidadController::class, 'unidades_view'])->name('unidades_view')->middleware('auth');
Route::get('/controlunidad/asignacion_view', [ControlUnidadController::class, 'asignacion_view'])->name('asignacion_view')->middleware('auth');
Route::get('/controlunidad/registrokm_view', [ControlUnidadController::class, 'registrokm_view'])->name('registrokm_view')->middleware('auth');
Route::get('/controlunidad/reportekm_view', [ControlUnidadController::class, 'reportekm_view'])->name('reportekm_view')->middleware('auth');
Route::get('/controlunidad/checklist_view', [ControlUnidadController::class, 'checklist_view'])->name('checklist_view')->middleware('auth');
Route::get('/controlunidad/checklistnew_view/{id}', [ControlUnidadController::class, 'checklistnew_view'])->name('checklistnew_view')->middleware('auth');
Route::get('/controlunidad/ordenesservicio_view', [ControlUnidadController::class, 'ordenesservicio_view'])->name('ordenesservicio_view')->middleware('auth');
Route::get('/controlunidad/ordenservicionew_view/{id}', [ControlUnidadController::class, 'ordenservicionew_view'])->name('ordenservicionew_view')->middleware('auth');
Route::get('/controlunidad/getUnidades', [ControlUnidadController::class, 'getUnidades']);
Route::get('/controlunidad/getAsignacionByIdUnidad/{idvehiculo}', [ControlUnidadController::class, 'getAsignacionByIdUnidad']);
Route::get('/controlunidad/getRegistroKilometraje', [ControlUnidadController::class, 'getRegistroKilometraje']);
Route::get('/controlunidad/getReporteRegistroKilometraje/{idunidad}/{fechade}/{fechaa}', [ControlUnidadController::class, 'getReporteRegistroKilometraje']);
Route::get('/controlunidad/getRegistrosCheckList', [ControlUnidadController::class, 'getRegistrosCheckList']);
Route::get('/controlunidad/getOrdenesServicio', [ControlUnidadController::class, 'getOrdenesServicio']);
Route::get('/controlunidad/getOrdenServicioPdf/{id}', [ControlUnidadController::class, 'getOrdenServicioPdf'])->name('getOrdenServicioPdf');
Route::post('/controlunidad/guardar_unidad', [ControlUnidadController::class, 'guardar_unidad']);
Route::post('/controlunidad/guardarAsignacion', [ControlUnidadController::class, 'guardarAsignacion']);
Route::post('/controlunidad/guardarRegistroKilometraje', [ControlUnidadController::class, 'guardarRegistroKilometraje']);
Route::post('/controlunidad/guardarRegistroCheckList', [ControlUnidadController::class, 'guardarRegistroCheckList']);
Route::post('/controlunidad/guardarOrdenServicio', [ControlUnidadController::class, 'guardarOrdenServicio']);

//RECURSOS HUMANOS
Route::get('/recursoshumanos/empleados_view', [RecursosHumanosController::class, 'empleados_view'])->name('empleados_view')->middleware('auth');
Route::post('/recursoshumanos/guardarEmpleado', [RecursosHumanosController::class, 'guardarEmpleado']);
Route::get('/recursoshumanos/getEmpleados', [RecursosHumanosController::class, 'getEmpleados']);

//REPORTES
Route::get('/reportes/reporte_resultado_operativo_view', [ReportesController::class, 'reporte_resultado_operativo_view'])->name('reporte_resultado_operativo_view')->middleware('auth');
Route::get('/reportes/getVentas/{periodo}/{negocio}/{sucursal}', [ReportesController::class, 'getVentas'])->name('ventas_listado');

//SINCRONIZADOR
Route::post('/api/save_ventas', [SincronizadorController::class, 'saveVentas']);

//CATALOGOS
Route::get('/catalogos/getNegocios', [CatalogosController::class, 'getNegocios']);
Route::get('/catalogos/getSucursales', [CatalogosController::class, 'getSucursales']);
Route::get('/catalogos/getTiposVehiculo', [CatalogosController::class, 'getTiposVehiculo']);
Route::get('/catalogos/getAnios', [CatalogosController::class, 'getAnios']);
Route::get('/catalogos/getMarcas', [CatalogosController::class, 'getMarcas']);
Route::get('/catalogos/getModelos/{idmarca}', [CatalogosController::class, 'getModelos']);
Route::get('/catalogos/getColores', [CatalogosController::class, 'getColores']);
Route::get('/catalogos/getDepartamentos', [CatalogosController::class, 'getDepartamentos']);
Route::get('/catalogos/getNivelGasolina', [CatalogosController::class, 'getNivelGasolina']);
Route::get('/catalogos/getPuestos', [CatalogosController::class, 'getPuestos']);
Route::get('/catalogos/getEstadoCivil', [CatalogosController::class, 'getEstadoCivil']);
Route::get('/catalogos/getTiposLicencia', [CatalogosController::class, 'getTiposLicencia']);
Route::get('/catalogos/getProveedores', [CatalogosController::class, 'getProveedores']);
Route::get('/catalogos/getProveedorById/{id}', [CatalogosController::class, 'getProveedorById']);
Route::get('/catalogos/getConceptosMantenimiento', [CatalogosController::class, 'getConceptosMantenimiento']);

//APIS PROYECTO FLUTTER
Route::get('/recursoshumanos/getEmpleadosFlutter', [RecursosHumanosController::class, 'getEmpleadosFlutter']);

//OTROS
Route::get('home.index', function () {
    return "home index";
})->name('home.index');

Route::get('/', function () {
    return view('auth.login');
})->middleware('guest');

Route::get('login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('login_form', function(){
    $credenciales = request()->only('username', 'password');

    if(Auth::attempt($credenciales)){
        request()->session()->regenerate();

        return redirect('home');
    }

    return redirect('login');
})->name('login_form');

Route::post('logout_form', function(Request $request){
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('login');
})->name('logout_form');

/*Route::group(['middleware' => ['auth', 'verified']], function () {

    // Home
    Route::group(['prefix' => 'home', 'as' => 'home.'], function () {
        Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('index');
    });

    Route::group(['middleware' => ['role:Administrator']], function () {
        Route::group(['prefix' => 'users',  'as' => 'users.'], function () {
            Route::resource('/', \App\Http\Controllers\UserController::class);
        });
    });
});

require __DIR__ . '/auth.php';
*/
/*Route::view('/', 'backend.home.main');
Route::view('login', 'login');
Route::view('dashboard', 'dashboard');*/
