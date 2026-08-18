<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Flotilla\FlotillaService;
use App\Services\Flotilla\AlertaService;

class GenerarAlertasFlotilla extends Command
{
    protected $signature   = 'flotilla:generar-alertas';
    protected $description = 'Genera y actualiza alertas de mantenimiento y documentos para toda la flotilla';

    public function handle(): int
    {
        $this->info('[Flotilla] Iniciando generación de alertas — ' . now()->format('Y-m-d H:i:s'));

        $flotillaService = new FlotillaService();
        $alertaService   = new AlertaService($flotillaService);

        $resultados = $alertaService->generarAlertas();

        $this->info("[Flotilla] Alertas mantenimiento : {$resultados['mantenimiento']} nuevas");
        $this->info("[Flotilla] Alertas documentos    : {$resultados['documentos']} nuevas");
        $this->info("[Flotilla] Total alertas nuevas  : {$resultados['total']}");

        return Command::SUCCESS;
    }
}
