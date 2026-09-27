<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\ResumenReferencia;

final class ResumenController
{
    public function index(): void
    {
        View::render('dashboard/index', [
            'title' => 'Resumen', 'active' => '/',
            'months' => (new ResumenReferencia())->months(),
        ]);
    }

    public function results(): void
    {
        View::render('results/index', [
            'title' => 'Resultados', 'active' => '/resultados',
            'months' => (new ResumenReferencia())->months(),
        ]);
    }
}
