<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public const CONFIG_PERMISSION = '/sistemas/configuracion';

    /**
     * GET /sistemas/configuracion
     * Devuelve la configuración del sistema (solo usuarios con permiso).
     */
    public function getSettings(Request $request)
    {
        $this->authorizeConfig($request);

        $settings = [];
        if (Schema::hasTable('system_settings')) {
            $settings = DB::table('system_settings')->pluck('value', 'key')->toArray();
        }

        return response()->json(['settings' => $settings]);
    }

    /**
     * PUT /sistemas/configuracion
     * Actualiza la configuración del sistema.
     */
    public function updateSettings(Request $request)
    {
        $this->authorizeConfig($request);

        $request->validate([
            'settings'                        => 'required|array',
            'settings.ocultar_menu_sin_permiso' => 'sometimes|in:0,1',
        ]);

        foreach ($request->settings as $key => $value) {
            DB::table('system_settings')
                ->updateOrInsert(
                    ['key' => $key],
                    ['value' => (string) $value, 'updated_at' => now()]
                );
        }

        $settings = DB::table('system_settings')->pluck('value', 'key')->toArray();

        return response()->json(['settings' => $settings]);
    }

    protected function authorizeConfig(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'No autenticado');
        }

        $tienePermiso = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_path', self::CONFIG_PERMISSION)
            ->exists();

        if (!$tienePermiso) {
            abort(403, 'No tienes permiso para ver la configuración.');
        }
    }
}
