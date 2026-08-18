<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RecursosHumanosController;
use App\Http\Controllers\OperacionesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CatalogosController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\FinanzasController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ActivosFijosController;
use App\Http\Controllers\AseguradoraController;
use App\Http\Controllers\TallerController;
use App\Http\Controllers\TipoServicioController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\CombustibleController;
use App\Http\Controllers\ControlNotasCreditoController;
use App\Http\Controllers\FormatosRhController;
use App\Http\Controllers\CalendarioController;
use App\Services\MenuService;
use App\Http\Controllers\Flotilla\FlotillaPlantillaController;
use App\Http\Controllers\Flotilla\FlotillaUnidadMantenimientoController;
use App\Http\Controllers\Flotilla\FlotillaMantenimientoPreventivController;
use App\Http\Controllers\Flotilla\FlotillaMantenimientoCorrectivoController;
use App\Http\Controllers\Flotilla\FlotillaCatRefaccionesController;
use App\Http\Controllers\Flotilla\FlotillaDocumentosController;
use App\Http\Controllers\Flotilla\FlotillaAlertasController;
use App\Http\Controllers\Flotilla\FlotillaBitacoraController;
use App\Http\Controllers\Flotilla\FlotillaDashboardController;
use App\Http\Controllers\Flotilla\FlotillaReportesController;
use App\Http\Controllers\Flotilla\FlotillaLecturasKmController;
use App\Http\Controllers\Combustible\CombustibleDashboardController;
use App\Http\Controllers\Combustible\CombustiblePresupuestosController;
use App\Http\Controllers\Combustible\CombustibleReportesController;
use App\Http\Controllers\Combustible\CombustibleAlertasController;
use App\Http\Controllers\Finanzas\ERDashboardController;
use App\Http\Controllers\RH\RHDashboardController;
use App\Http\Controllers\RH\RHMovimientosController;
use App\Http\Controllers\RH\RHDocumentosController;
use App\Http\Controllers\RH\RHAlertasController;
use App\Http\Controllers\RH\RHReportesController;

use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware('api')->group(function () {

    // ─── USUARIOS / SISTEMAS ─────────────────────────────────────────────────
    Route::get('/sistemas/users', [UserController::class, 'getUsuarios']);
    Route::get('/sistemas/users/me/obtener-usuarios-sucursal-combustible', [UserController::class, 'getCurrentUserSucursalCombustible'])->middleware('auth:sanctum');
    Route::get('/sistemas/users/{id}', [UserController::class, 'getUsuario']);
    Route::post('/sistemas/users', [UserController::class, 'guardarUsuario']);
    Route::put('/sistemas/users/{id}', [UserController::class, 'guardarUsuario']);
    Route::get('/sistemas/users/{id}/obtener-usuarios-sucursal-combustible', [UserController::class, 'getUserSucursalCombustible']);
    Route::put('/sistemas/users/{id}/guardar-usuarios-sucursal-combustible', [UserController::class, 'saveUserSucursalCombustible']);

    // ─── PERMISOS ─────────────────────────────────────────────────────────────
    Route::get('/usuarios/{userId}/permissions', [UserController::class, 'show_permissions']);
    Route::put('/usuarios/{userId}/permissions', [UserController::class, 'update_permissions']);

    // ─── MENU ─────────────────────────────────────────────────────────────────
    Route::get('/usuarios/menu', function (MenuService $menuService) {
        return $menuService->buildMenuForUser(auth()->id());
    });
    Route::get('/menu-options', function (MenuService $menuService) {
        return $menuService->getMenuOpciones(auth()->id());
    });

    // ─── CONFIGURACIÓN DEL SISTEMA ────────────────────────────────────────────
    Route::get('/sistemas/configuracion', [SettingsController::class, 'getSettings'])->middleware('auth:sanctum');
    Route::put('/sistemas/configuracion', [SettingsController::class, 'updateSettings'])->middleware('auth:sanctum');

    // ─── AUTH ─────────────────────────────────────────────────────────────────
    Route::post('/login_vue', function (Request $request, MenuService $menuService) {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        $user["menu"] = $menuService->buildMenuForUser(auth()->id());
        $user["permissions"] = $menuService->getPermissionsForUser(auth()->id());
        $user["role"] = $user->role;

        return response()->json(['token' => $token, 'user' => $user]);
    });

    Route::post('/logout_vue', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    })->middleware('auth:sanctum');

    // ─── RECURSOS HUMANOS / EMPLEADOS ─────────────────────────────────────────
    Route::get('/recursoshumanos/empleados', [RecursosHumanosController::class, 'getEmpleadosFlutter']);
    Route::get('/recursoshumanos/empleados/documentos', [RecursosHumanosController::class, 'getDocumentosEmpleados']);
    Route::get('/recursoshumanos/empleados/{id}', [RecursosHumanosController::class, 'getEmpleado']);
    Route::post('/recursoshumanos/empleados', [RecursosHumanosController::class, 'guardarEmpleadoVue']);
    Route::put('/recursoshumanos/empleados/{id}', [RecursosHumanosController::class, 'guardarEmpleadoVue']);
    Route::get('/recursoshumanos/empleados/{id}/archivos', [RecursosHumanosController::class, 'getArchivosEmpleado']);
    Route::post('/recursoshumanos/empleados/{id}/archivos', [RecursosHumanosController::class, 'guardarArchivosEmpleado']);
    Route::get('/recursoshumanos/empleados/{id}/baja', [RecursosHumanosController::class, 'getBajaEmpleado']);
    Route::delete('/recursoshumanos/empleados/{id}', [ActivosFijosController::class, 'eliminarEmpleado']);

    // ─── RECURSOS HUMANOS / ACTIVOS FIJOS MOVIMIENTOS ─────────────────────────
    Route::get('/recursos-humanos/activos-fijos/movimientos', [RecursosHumanosController::class, 'getMovimientosActivosFijos']);

    // ─── RECURSOS HUMANOS / FORMATOS RH ───────────────────────────────────────
    Route::get('/catalogos/formatos-rh', [FormatosRhController::class, 'getFormatosRh']);
    Route::get('/catalogos/formatos-rh/{id}', [FormatosRhController::class, 'getFormatoRh']);
    Route::post('/catalogos/formatos-rh', [FormatosRhController::class, 'guardarFormatoRh']);
    Route::post('/catalogos/formatos-rh/{id}', [FormatosRhController::class, 'guardarFormatoRh']);  // POST con ID para update con archivo
    Route::delete('/catalogos/formatos-rh/{id}', [FormatosRhController::class, 'eliminarFormatoRh']);
    Route::get('/catalogos/tipos-formato-rh', [FormatosRhController::class, 'getTiposFormatoRh']);

    // ─── CALENDARIO ────────────────────────────────────────────────────────────
    Route::get('/catalogos/calendario', [CalendarioController::class, 'getEventos']);
    Route::get('/catalogos/calendario/{id}', [CalendarioController::class, 'getEvento']);
    Route::post('/catalogos/calendario', [CalendarioController::class, 'guardarEvento']);
    Route::post('/catalogos/calendario/{id}', [CalendarioController::class, 'guardarEvento']);
    Route::delete('/catalogos/calendario/{id}', [CalendarioController::class, 'eliminarEvento']);

    // ─── OPERACIONES / VEHÍCULOS (ACTIVOS FIJOS + COMBUSTIBLE) ────────────────
    Route::get('/operaciones/vehiculos', [OperacionesController::class, 'getUnidades']);

    // ─── OPERACIONES / CAPTURA TICKETS ────────────────────────────────────────
    Route::get('/operaciones/combustible/lista-tickets', [CombustibleController::class, 'getListaTickets']);
    Route::post('/operaciones/combustible/captura-ticket', [CombustibleController::class, 'guardarCapturaTicket']);
    Route::put('/operaciones/combustible/captura-ticket/{id}', [CombustibleController::class, 'guardarCapturaTicket']);
    Route::delete('/operaciones/combustible/eliminar-ticket/{id}', [CombustibleController::class, 'eliminarCapturaTicket']);
    Route::get('/operaciones/combustible/{id}/captura-ticket', [CombustibleController::class, 'getCapturaTicket']);

    // ─── OPERACIONES / COMBUSTIBLE (legacy) ───────────────────────────────────
    /*Route::get('/operaciones/combustible', [CombustibleController::class, 'getCombustibles']);
    Route::get('/operaciones/combustible/{id}', [CombustibleController::class, 'getCombustible']);
    Route::post('/operaciones/combustible', [CombustibleController::class, 'guardarCombustible']);
    Route::put('/operaciones/combustible/{id}', [CombustibleController::class, 'guardarCombustible']);
    Route::delete('/operaciones/combustible/{id}', [CombustibleController::class, 'eliminarCombustible']);*/

    Route::get('/operaciones/vehiculos/mantenimientos', [CombustibleController::class, 'getMantenimientos']);
    Route::get('/operaciones/vehiculos/rendimiento', [CombustibleController::class, 'getRendimiento']);
    Route::get('/operaciones/vehiculos/costo-km', [CombustibleController::class, 'getCostoKm']);

    // ─── OPERACIONES / ÓRDENES DE SERVICIO ────────────────────────────────────
    Route::get('/operaciones/ordenes-servicio', [OrdenServicioController::class, 'getOrdenesServicio']);
    Route::get('/operaciones/ordenes-servicio/{id}', [OrdenServicioController::class, 'getOrdenServicio']);
    Route::get('/operaciones/ordenes-servicio/{id}/pdf', [OrdenServicioController::class, 'getOrdenServicioPdf']);
    Route::post('/operaciones/ordenes-servicio', [OrdenServicioController::class, 'guardarOrdenServicio']);
    Route::put('/operaciones/ordenes-servicio/{id}', [OrdenServicioController::class, 'guardarOrdenServicio']);
    Route::delete('/operaciones/ordenes-servicio/{id}', [OrdenServicioController::class, 'eliminarOrdenServicio']);
    // ─── ESTADO DE RESULTADOS GERENCIAL ─────────────────────────────────────
    Route::get('/er/dashboard', [ERDashboardController::class, 'getDashboard']);
    Route::get('/er/filtros',   [ERDashboardController::class, 'getFiltros']);
    // ─── FINANZAS ─────────────────────────────────────────────────────────────
    Route::get('/finanzas/cumplimiento-objetivos/detalle', [FinanzasController::class, 'getCumplimientoObjetivoDetalle']);
    Route::get('/finanzas/cumplimiento-objetivos/concentrado', [FinanzasController::class, 'getCumplimientoObjetivosConcentrado']);
    Route::post('/finanzas/cumplimiento-objetivos/guardar-captura-factura', [FinanzasController::class, 'guardarCapturaFactura']);
    Route::put('/finanzas/cumplimiento-objetivos/guardar-captura-factura/{id}', [FinanzasController::class, 'guardarCapturaFactura']);
    Route::delete('/finanzas/cumplimiento-objetivos/eliminar-captura-factura/{id}', [FinanzasController::class, 'eliminarCapturaFactura']);

    Route::get('/finanzas/control-notas-credito', [ControlNotasCreditoController::class, 'getNotasCredito']);
    Route::get('/finanzas/control-notas-credito/{id}', [ControlNotasCreditoController::class, 'getNotaCredito']);
    Route::post('/finanzas/control-notas-credito', [ControlNotasCreditoController::class, 'guardarNotaCredito']);
    Route::put('/finanzas/control-notas-credito/{id}', [ControlNotasCreditoController::class, 'guardarNotaCredito']);
    Route::delete('/finanzas/control-notas-credito/{id}', [ControlNotasCreditoController::class, 'eliminarNotaCredito']);

    // ─── CATÁLOGOS / ADENDUM ──────────────────────────────────────────────────
    Route::get('/catalogos/adendum/{id}', [FinanzasController::class, 'getAdendum']);
    Route::get('/catalogos/adendum', [FinanzasController::class, 'getAdendums']);
    Route::post('/catalogos/adendum', [FinanzasController::class, 'guardarAdendum']);
    Route::put('/catalogos/adendum/{id}', [FinanzasController::class, 'guardarAdendum']);

    // ─── CATÁLOGOS / ACTIVOS FIJOS ────────────────────────────────────────────
    Route::get('/catalogos/activos-fijos-excel', [ActivosFijosController::class, 'exportExcel']);
    Route::get('/catalogos/activos-fijos', [ActivosFijosController::class, 'getActivosFijos']);
    Route::get('/catalogos/activos-fijos/{id}', [ActivosFijosController::class, 'getActivoFijo']);
    Route::post('/catalogos/activos-fijos', [ActivosFijosController::class, 'guardarActivoFijo']);
    Route::put('/catalogos/activos-fijos/{id}', [ActivosFijosController::class, 'guardarActivoFijo']);
    Route::get('/catalogos/activos-fijos/{id}/archivos', [ActivosFijosController::class, 'getArchivosActivoFijo']);
    Route::post('/catalogos/activos-fijos/{id}/archivos', [ActivosFijosController::class, 'guardarArchivosActivoFijo']);
    Route::delete('/catalogos/activos-fijos/{id}', [ActivosFijosController::class, 'eliminarActivoFijo']);

    // ─── CATÁLOGOS / ASIGNACIÓN ACTIVOS ───────────────────────────────────────
    Route::post('/operaciones/control-activos/asignacion-activos', [ActivosFijosController::class, 'guardarAsignacionActivoFijo']);
    Route::put('/operaciones/control-activos/asignacion-activos/{id}', [ActivosFijosController::class, 'guardarAsignacionActivoFijo']);
    Route::get('/operaciones/control-activos/asignacion-activos', [ActivosFijosController::class, 'getAsignacionesActivosFijos']);
    Route::get('/operaciones/control-activos/asignacion-activos/{id}', [ActivosFijosController::class, 'getAsignacionActivoFijo']);

    // ─── CATÁLOGOS / TALLERES (PROVEEDORES) ───────────────────────────────────
    Route::get('/catalogos/talleres', [TallerController::class, 'getTalleres']);
    Route::get('/catalogos/talleres/{id}', [TallerController::class, 'getTaller']);
    Route::post('/catalogos/talleres', [TallerController::class, 'guardarTaller']);
    Route::put('/catalogos/talleres/{id}', [TallerController::class, 'guardarTaller']);
    Route::delete('/catalogos/talleres/{id}', [TallerController::class, 'eliminarTaller']);

    // ─── CATÁLOGOS / TIPOS DE SERVICIO ────────────────────────────────────────
    Route::get('/catalogos/tipos-servicio', [TipoServicioController::class, 'getTiposServicio']);
    Route::get('/catalogos/tipos-servicio/{id}', [TipoServicioController::class, 'getTipoServicio']);
    Route::post('/catalogos/tipos-servicio', [TipoServicioController::class, 'guardarTipoServicio']);
    Route::put('/catalogos/tipos-servicio/{id}', [TipoServicioController::class, 'guardarTipoServicio']);
    Route::delete('/catalogos/tipos-servicio/{id}', [TipoServicioController::class, 'eliminarTipoServicio']);

    // ─── CATÁLOGOS / NEGOCIOS ─────────────────────────────────────────────────
    Route::get('/catalogos/negocios/{id}', [NegocioController::class, 'getNegocio']);
    Route::get('/catalogos/negocios', [NegocioController::class, 'getNegocios']);
    Route::post('/catalogos/negocios', [NegocioController::class, 'guardarNegocio']);
    Route::put('/catalogos/negocios/{id}', [NegocioController::class, 'guardarNegocio']);

    // ─── CATÁLOGOS / ASEGURADORAS ─────────────────────────────────────────────
    Route::get('/catalogos/aseguradoras/{id}', [AseguradoraController::class, 'getAseguradora']);
    Route::get('/catalogos/aseguradoras', [AseguradoraController::class, 'getAseguradoras']);
    Route::post('/catalogos/aseguradoras', [AseguradoraController::class, 'guardarAseguradora']);
    Route::put('/catalogos/aseguradoras/{id}', [AseguradoraController::class, 'guardarAseguradora']);

    // ─── CATÁLOGOS / SUCURSALES ───────────────────────────────────────────────
    Route::get('/catalogos/sucursales/{id}', [SucursalController::class, 'getSucursal']);
    Route::get('/catalogos/sucursales', [SucursalController::class, 'getSucursales']);
    Route::post('/catalogos/sucursales', [SucursalController::class, 'guardarSucursal']);
    Route::put('/catalogos/sucursales/{id}', [SucursalController::class, 'guardarSucursal']);

    // ─── CATÁLOGOS / LOOKUP (sin CRUD) ───────────────────────────────────────
    Route::get('/catalogos/estatus-activos-fijos', [CatalogosController::class, 'getEstatusActivosFijos']);
    Route::get('/catalogos/estatus-rh', [CatalogosController::class, 'getEstatusRH']);
    Route::get('/catalogos/tipos-activos-fijos', [CatalogosController::class, 'getTiposActivosFijos']);
    Route::get('/catalogos/condiciones', [CatalogosController::class, 'getCondiciones']);
    Route::get('/catalogos/sucursalesinroute', [CatalogosController::class, 'getSucursales']);
    Route::get('/catalogos/negociosinroute', [CatalogosController::class, 'getNegocios']);
    Route::get('/catalogos/anios', [CatalogosController::class, 'getAnios']);
    Route::get('/catalogos/puestos', [CatalogosController::class, 'getPuestos']);
    Route::get('/catalogos/departamentos', [CatalogosController::class, 'getDepartamentos']);
    Route::get('/catalogos/estadoscivil', [CatalogosController::class, 'getEstadoCivil']);
    Route::get('/catalogos/tipos-vehiculo', [CatalogosController::class, 'getTiposVehiculo']);
    Route::get('/catalogos/tiposvehiculo', [CatalogosController::class, 'getTiposVehiculo']);        // alias VehiculoForm
    Route::get('/catalogos/tipos-combustible', [CatalogosController::class, 'getTiposCombustible']);
    Route::get('/catalogos/tiposcombustible', [CatalogosController::class, 'getTiposCombustible']);  // alias VehiculoForm
    Route::get('/catalogos/tipos-transmision', [CatalogosController::class, 'getTiposTransmision']);
    Route::get('/catalogos/tipos-cobertura-seguro', [CatalogosController::class, 'getTiposCoberturaSeguro']);
    Route::get('/catalogos/marcasvehiculo', [CatalogosController::class, 'getMarcas']);              // alias VehiculoForm
    Route::get('/catalogos/tipos-proveedor', [CatalogosController::class, 'getTiposProveedor']);
    Route::get('/catalogos/rutas', [CatalogosController::class, 'getRutas']);

    // ─── REPORTES ─────────────────────────────────────────────────────────────
    Route::get('/reportes/utilidad-operativa', [ReportesController::class, 'listaReporteUtilidadJson']);
    Route::get('/reportes/estado-resultados', [ReportesController::class, 'getEstadoResultados']);
    Route::get('/reportes/estado-resultados/detalle', [ReportesController::class, 'getEstadoResultadosDetalle']);
    Route::get('/operaciones/reportes/analisis-mantenimiento', [ReportesController::class, 'getAnalisisMantenimiento']);
    Route::get('/operaciones/reportes/analisis-mantenimiento-excel', [ReportesController::class, 'exportAnalisisMantenimientoExcel']);

    // ═══════════════════════════════════════════════════════════════════════════
    // ─── FLOTILLA — GESTIÓN INTEGRAL DE FLOTILLA ──────────────────────────────
    // ═══════════════════════════════════════════════════════════════════════════

    // Dashboard
    Route::get('/flotilla/dashboard', [FlotillaDashboardController::class, 'getDashboard']);
    Route::post('/flotilla/dashboard/autorizar', [FlotillaDashboardController::class, 'autorizarPendiente'])->middleware('auth:sanctum');

    // Plantillas de mantenimiento por tipo de unidad
    Route::get('/flotilla/plantillas',                                    [FlotillaPlantillaController::class, 'getPlantillas']);
    Route::get('/flotilla/plantillas/{id}',                               [FlotillaPlantillaController::class, 'getPlantilla']);
    Route::post('/flotilla/plantillas',                                   [FlotillaPlantillaController::class, 'guardarPlantilla']);
    Route::put('/flotilla/plantillas/{id}',                               [FlotillaPlantillaController::class, 'guardarPlantilla']);
    Route::delete('/flotilla/plantillas/{id}',                            [FlotillaPlantillaController::class, 'eliminarPlantilla']);
    Route::get('/flotilla/plantillas/{idPlantilla}/servicios',            [FlotillaPlantillaController::class, 'getServiciosPlantilla']);
    Route::post('/flotilla/plantillas/{idPlantilla}/servicios',           [FlotillaPlantillaController::class, 'guardarServicioPlantilla']);
    Route::put('/flotilla/plantillas/{idPlantilla}/servicios/{id}',       [FlotillaPlantillaController::class, 'guardarServicioPlantilla']);
    Route::delete('/flotilla/plantillas/{idPlantilla}/servicios/{id}',    [FlotillaPlantillaController::class, 'eliminarServicioPlantilla']);

    // Schedule de mantenimiento por unidad
    Route::get('/flotilla/unidades/estado',                               [FlotillaUnidadMantenimientoController::class, 'getEstadoUnidades']);
    Route::post('/flotilla/unidades/inicializar-lote',                    [FlotillaUnidadMantenimientoController::class, 'inicializarLote']);
    Route::get('/flotilla/unidades/resumen',                              [FlotillaUnidadMantenimientoController::class, 'getResumenFlotilla']);
    Route::get('/flotilla/unidades/{idActivoFijo}/plantillas-disponibles',[FlotillaUnidadMantenimientoController::class, 'getPlantillasDisponibles']);
    Route::put('/flotilla/unidades/{idActivoFijo}/plantilla',             [FlotillaUnidadMantenimientoController::class, 'cambiarPlantilla']);
    Route::get('/flotilla/unidades/{idActivoFijo}/mantenimiento',         [FlotillaUnidadMantenimientoController::class, 'getScheduleUnidad']);
    Route::post('/flotilla/unidades/{idActivoFijo}/inicializar',          [FlotillaUnidadMantenimientoController::class, 'inicializarMantenimiento']);
    Route::post('/flotilla/unidades/{idActivoFijo}/mantenimiento',        [FlotillaUnidadMantenimientoController::class, 'agregarServicioManual']);
    Route::put('/flotilla/unidades/{idActivoFijo}/mantenimiento/{id}',    [FlotillaUnidadMantenimientoController::class, 'actualizarServicio']);
    Route::put('/flotilla/unidades/{idActivoFijo}/mantenimiento/{id}/posponer', [FlotillaUnidadMantenimientoController::class, 'posponerMantenimiento']);
    Route::delete('/flotilla/unidades/{idActivoFijo}/mantenimiento/{id}', [FlotillaUnidadMantenimientoController::class, 'eliminarServicio']);

    // Mantenimiento Preventivo
    Route::get('/flotilla/mantenimiento/preventivo',                      [FlotillaMantenimientoPreventivController::class, 'getMantenimientos']);
    Route::get('/flotilla/mantenimiento/preventivo/{id}',                 [FlotillaMantenimientoPreventivController::class, 'getMantenimiento']);
    Route::post('/flotilla/mantenimiento/preventivo',                     [FlotillaMantenimientoPreventivController::class, 'guardarMantenimiento']);
    Route::put('/flotilla/mantenimiento/preventivo/{id}',                 [FlotillaMantenimientoPreventivController::class, 'guardarMantenimiento']);
    Route::delete('/flotilla/mantenimiento/preventivo/{id}',              [FlotillaMantenimientoPreventivController::class, 'eliminarMantenimiento']);
    Route::put('/flotilla/mantenimiento/preventivo/{id}/autorizacion',    [FlotillaMantenimientoPreventivController::class, 'autorizarMantenimiento'])->middleware('auth:sanctum');

    // Mantenimiento Correctivo
    Route::get('/flotilla/mantenimiento/correctivo',                      [FlotillaMantenimientoCorrectivoController::class, 'getMantenimientos']);
    Route::get('/flotilla/mantenimiento/correctivo/{id}',                 [FlotillaMantenimientoCorrectivoController::class, 'getMantenimiento']);
    Route::post('/flotilla/mantenimiento/correctivo',                     [FlotillaMantenimientoCorrectivoController::class, 'guardarMantenimiento']);
    Route::put('/flotilla/mantenimiento/correctivo/{id}',                 [FlotillaMantenimientoCorrectivoController::class, 'guardarMantenimiento']);
    Route::put('/flotilla/mantenimiento/correctivo/{id}/estatus',         [FlotillaMantenimientoCorrectivoController::class, 'actualizarEstatus']);
    Route::delete('/flotilla/mantenimiento/correctivo/{id}',              [FlotillaMantenimientoCorrectivoController::class, 'eliminarMantenimiento']);
    Route::put('/flotilla/mantenimiento/correctivo/{id}/autorizacion',    [FlotillaMantenimientoCorrectivoController::class, 'autorizarMantenimiento'])->middleware('auth:sanctum');

    // Catálogo de Refacciones / Partes
    Route::get('/flotilla/cat-refacciones',                               [FlotillaCatRefaccionesController::class, 'getCatRefacciones']);
    Route::get('/flotilla/cat-refacciones/categorias',                    [FlotillaCatRefaccionesController::class, 'getCategorias']);
    Route::get('/flotilla/cat-refacciones/{id}',                          [FlotillaCatRefaccionesController::class, 'getCatRefaccion']);
    Route::post('/flotilla/cat-refacciones',                              [FlotillaCatRefaccionesController::class, 'guardarCatRefaccion']);
    Route::put('/flotilla/cat-refacciones/{id}',                          [FlotillaCatRefaccionesController::class, 'guardarCatRefaccion']);
    Route::delete('/flotilla/cat-refacciones/{id}',                       [FlotillaCatRefaccionesController::class, 'eliminarCatRefaccion']);

    // Documentos de Unidad (seguros, tarjeta circulación, verificación, etc.)
    Route::get('/flotilla/documentos',                                    [FlotillaDocumentosController::class, 'getDocumentosFlotilla']);
    Route::get('/flotilla/unidades/{idActivoFijo}/documentos',            [FlotillaDocumentosController::class, 'getDocumentosUnidad']);
    Route::get('/flotilla/documentos/{id}',                               [FlotillaDocumentosController::class, 'getDocumento']);
    Route::post('/flotilla/documentos',                                   [FlotillaDocumentosController::class, 'guardarDocumento']);
    Route::post('/flotilla/documentos/{id}',                              [FlotillaDocumentosController::class, 'guardarDocumento']); // POST para update con archivo
    Route::delete('/flotilla/documentos/{id}',                            [FlotillaDocumentosController::class, 'eliminarDocumento']);

    // Alertas
    Route::get('/flotilla/alertas',                                       [FlotillaAlertasController::class, 'getAlertas']);
    Route::get('/flotilla/alertas/resumen',                               [FlotillaAlertasController::class, 'getResumenAlertas']);
    Route::post('/flotilla/alertas/generar',                              [FlotillaAlertasController::class, 'generarAlertas']);
    Route::put('/flotilla/alertas/{id}/leida',                            [FlotillaAlertasController::class, 'marcarLeida']);
    Route::put('/flotilla/alertas/marcar-todas-leidas',                   [FlotillaAlertasController::class, 'marcarTodasLeidas']);

    // Bitácora / Timeline
    Route::get('/flotilla/bitacora',                                      [FlotillaBitacoraController::class, 'getBitacoraGeneral']);
    Route::get('/flotilla/unidades/{idActivoFijo}/bitacora',              [FlotillaBitacoraController::class, 'getBitacoraUnidad']);

    // Reportes
    Route::get('/flotilla/reportes/mantenimientos',                       [FlotillaReportesController::class, 'getReporteMantenimientos']);
    Route::get('/flotilla/reportes/exportar-excel',                       [FlotillaReportesController::class, 'exportarExcel']);
    Route::get('/flotilla/reportes/exportar-pdf',                         [FlotillaReportesController::class, 'exportarPdf']);

    // ═══════════════════════════════════════════════════════════════════════════
    // ─── CONTROL DE COMBUSTIBLE ───────────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════════════

    // Dashboard
    Route::get('/combustible/dashboard',                                  [CombustibleDashboardController::class, 'getDashboard']);

    // Presupuestos
    Route::get('/combustible/presupuestos',                               [CombustiblePresupuestosController::class, 'getPresupuestos']);
    Route::get('/combustible/presupuestos/{id}',                          [CombustiblePresupuestosController::class, 'getPresupuesto']);
    Route::post('/combustible/presupuestos',                              [CombustiblePresupuestosController::class, 'guardarPresupuesto']);
    Route::put('/combustible/presupuestos/{id}',                          [CombustiblePresupuestosController::class, 'guardarPresupuesto']);
    Route::delete('/combustible/presupuestos/{id}',                       [CombustiblePresupuestosController::class, 'eliminarPresupuesto']);

    // Reportes
    Route::get('/combustible/reportes',                                   [CombustibleReportesController::class, 'getReporte']);
    Route::get('/combustible/reportes/exportar-csv',                      [CombustibleReportesController::class, 'exportarCsv']);
    Route::get('/combustible/reportes/rendimiento',                       [CombustibleReportesController::class, 'getReporteRendimiento']);
    Route::get('/combustible/reportes/resumen-sucursal',                  [CombustibleReportesController::class, 'getResumenSucursal']);

    // Alertas y Validaciones
    Route::get('/combustible/alertas',                                    [CombustibleAlertasController::class, 'getAlertas']);
    Route::put('/combustible/alertas/{id}/leida',                         [CombustibleAlertasController::class, 'marcarLeida']);
    Route::put('/combustible/alertas/marcar-todas-leidas',                [CombustibleAlertasController::class, 'marcarTodasLeidas']);
    Route::post('/combustible/alertas/validar-ticket/{idTicket}',         [CombustibleAlertasController::class, 'validarTicket']);
    // Reglas
    Route::get('/combustible/reglas-validacion',                          [CombustibleAlertasController::class, 'getReglas']);
    Route::post('/combustible/reglas-validacion',                         [CombustibleAlertasController::class, 'guardarRegla']);
    Route::put('/combustible/reglas-validacion/{id}',                     [CombustibleAlertasController::class, 'guardarRegla']);
    Route::delete('/combustible/reglas-validacion/{id}',                  [CombustibleAlertasController::class, 'eliminarRegla']);

    // Lecturas de Kilometraje
    Route::get('/flotilla/lecturas-km/dashboard',                         [FlotillaLecturasKmController::class, 'getDashboard']);
    Route::get('/flotilla/lecturas-km/captura-masiva',                    [FlotillaLecturasKmController::class, 'getCapturaMasiva']);
    Route::post('/flotilla/lecturas-km',                                  [FlotillaLecturasKmController::class, 'guardarLecturasKm']);
    Route::get('/flotilla/lecturas-km/{idActivoFijo}/historial',          [FlotillaLecturasKmController::class, 'getHistorialUnidad']);

    // ═══════════════════════════════════════════════════════════════════════════
    // ─── RECURSOS HUMANOS — MÓDULO GESTIÓN DE PERSONAL ────────────────────────
    // ═══════════════════════════════════════════════════════════════════════════

    // Dashboard
    Route::get('/rh/dashboard',                                           [RHDashboardController::class, 'getDashboard']);

    // Movimientos de personal
    Route::get('/rh/movimientos',                                         [RHMovimientosController::class, 'getMovimientos']);
    Route::get('/rh/movimientos/tipos',                                   [RHMovimientosController::class, 'getTiposMovimiento']);
    Route::get('/rh/movimientos/{id}',                                    [RHMovimientosController::class, 'getMovimiento']);
    Route::post('/rh/movimientos',                                        [RHMovimientosController::class, 'guardarMovimiento']);
    Route::put('/rh/movimientos/{id}',                                    [RHMovimientosController::class, 'guardarMovimiento']);
    Route::delete('/rh/movimientos/{id}',                                 [RHMovimientosController::class, 'eliminarMovimiento']);
    Route::get('/rh/empleados/{idempleado}/movimientos',                  [RHMovimientosController::class, 'getHistorialEmpleado']);

    // Expediente digital / documentos
    Route::get('/rh/documentos',                                          [RHDocumentosController::class, 'getDocumentosFlotilla']);
    Route::get('/rh/empleados/{idempleado}/documentos',                   [RHDocumentosController::class, 'getDocumentosEmpleado']);
    Route::get('/rh/empleados/{idempleado}/expediente-resumen',           [RHDocumentosController::class, 'getResumenExpediente']);
    Route::post('/rh/documentos',                                         [RHDocumentosController::class, 'guardarDocumento']);
    Route::post('/rh/documentos/{id}',                                    [RHDocumentosController::class, 'guardarDocumento']); // POST para update con archivo
    Route::delete('/rh/documentos/{id}',                                  [RHDocumentosController::class, 'eliminarDocumento']);

    // Alertas RH
    Route::get('/rh/alertas',                                             [RHAlertasController::class, 'getAlertas']);
    Route::get('/rh/alertas/resumen',                                     [RHAlertasController::class, 'getResumen']);
    Route::post('/rh/alertas/generar',                                    [RHAlertasController::class, 'generarAlertas']);
    Route::put('/rh/alertas/{id}/leida',                                  [RHAlertasController::class, 'marcarLeida']);
    Route::put('/rh/alertas/marcar-todas-leidas',                         [RHAlertasController::class, 'marcarTodasLeidas']);

    // Reportes RH
    Route::get('/rh/reportes/plantilla-laboral',                          [RHReportesController::class, 'getPlantillaLaboral']);
    Route::get('/rh/reportes/historial-movimientos',                      [RHReportesController::class, 'getHistorialMovimientos']);
    Route::get('/rh/reportes/altas-bajas',                                [RHReportesController::class, 'getReporteAltasBajas']);
    Route::get('/rh/reportes/antiguedad',                                 [RHReportesController::class, 'getReporteAntiguedad']);
    Route::get('/rh/reportes/exportar-plantilla-csv',                     [RHReportesController::class, 'exportarCsvPlantilla']);
});


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    //Route::apiResource('users', UserController::class);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Fin de rutas