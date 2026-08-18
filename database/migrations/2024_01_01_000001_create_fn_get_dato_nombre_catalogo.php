<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("DROP FUNCTION IF EXISTS fnGetDatoNombreCatalogoById");

        // MySQL no permite SQL dinámico dentro de funciones (PREPARE/EXECUTE).
        // Se usa un CASE estático con todas las tablas de catálogo del sistema.
        DB::unprepared("
CREATE FUNCTION fnGetDatoNombreCatalogoById(p_tabla VARCHAR(100), p_id INT)
RETURNS VARCHAR(255)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_result VARCHAR(255) DEFAULT NULL;

    CASE p_tabla
        WHEN 'cat_departamentos' THEN
            SELECT nombre INTO v_result FROM cat_departamentos WHERE id = p_id LIMIT 1;
        WHEN 'cat_puestos' THEN
            SELECT nombre INTO v_result FROM cat_puestos WHERE id = p_id LIMIT 1;
        WHEN 'cat_estado_civil' THEN
            SELECT nombre INTO v_result FROM cat_estado_civil WHERE id = p_id LIMIT 1;
        WHEN 'cat_condiciones' THEN
            SELECT nombre INTO v_result FROM cat_condiciones WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipos_activos_fijos' THEN
            SELECT nombre INTO v_result FROM cat_tipos_activos_fijos WHERE id = p_id LIMIT 1;
        WHEN 'cat_estatus_activos_fijos' THEN
            SELECT nombre INTO v_result FROM cat_estatus_activos_fijos WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipo_vehiculo' THEN
            SELECT nombre INTO v_result FROM cat_tipo_vehiculo WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipos_combustible' THEN
            SELECT nombre INTO v_result FROM cat_tipos_combustible WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipos_transmision' THEN
            SELECT nombre INTO v_result FROM cat_tipos_transmision WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipo_cobertura_seguro' THEN
            SELECT nombre INTO v_result FROM cat_tipo_cobertura_seguro WHERE id = p_id LIMIT 1;
        WHEN 'cat_marcas' THEN
            SELECT nombre INTO v_result FROM cat_marcas WHERE id = p_id LIMIT 1;
        WHEN 'cat_modelos' THEN
            SELECT nombre INTO v_result FROM cat_modelos WHERE id = p_id LIMIT 1;
        WHEN 'cat_anios' THEN
            SELECT nombre INTO v_result FROM cat_anios WHERE id = p_id LIMIT 1;
        WHEN 'cat_colores' THEN
            SELECT nombre INTO v_result FROM cat_colores WHERE id = p_id LIMIT 1;
        WHEN 'negocios' THEN
            SELECT nombre INTO v_result FROM negocios WHERE id = p_id LIMIT 1;
        WHEN 'sucursales' THEN
            SELECT nombre INTO v_result FROM sucursales WHERE id = p_id LIMIT 1;
        WHEN 'aseguradoras' THEN
            SELECT nombre INTO v_result FROM aseguradoras WHERE id = p_id LIMIT 1;
        WHEN 'empleados' THEN
            SELECT COALESCE(nombrecompleto, nombres) INTO v_result FROM empleados WHERE id = p_id LIMIT 1;
        WHEN 'talleres' THEN
            SELECT COALESCE(razonsocial, nombrecorto) INTO v_result FROM talleres WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipos_servicio' THEN
            SELECT nombre INTO v_result FROM cat_tipos_servicio WHERE id = p_id LIMIT 1;
        WHEN 'cat_tipos_proveedor' THEN
            SELECT nombre INTO v_result FROM cat_tipos_proveedor WHERE id = p_id LIMIT 1;
        ELSE
            SET v_result = NULL;
    END CASE;

    RETURN v_result;
END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP FUNCTION IF EXISTS fnGetDatoNombreCatalogoById;");
    }
};
