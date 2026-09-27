<?php
declare(strict_types=1);

namespace App\Models;

/** Read-only reference from the reviewed workbook. Not a database repository. */
final class ResumenReferencia
{
    public function months(): array
    {
        return [
            ['month' => 'Enero', 'short' => 'Ene', 'continuity' => 16.83744755696121, 'pressure' => 22.86186374491706, 'connections' => 30986],
            ['month' => 'Febrero', 'short' => 'Feb', 'continuity' => 14.955343821580572, 'pressure' => 22.43816349092801, 'connections' => 30754],
            ['month' => 'Marzo', 'short' => 'Mar', 'continuity' => 15.327731219073323, 'pressure' => 24.29714299595142, 'connections' => 30875],
            ['month' => 'Abril', 'short' => 'Abr', 'continuity' => 14.765501578932325, 'pressure' => 23.52598990483539, 'connections' => 31104],
            ['month' => 'Mayo', 'short' => 'May', 'continuity' => 17.242026130733578, 'pressure' => 26.315912098449733, 'connections' => 31285],
            ['month' => 'Junio', 'short' => 'Jun', 'continuity' => 18.204526057969478, 'pressure' => 26.659942702530643, 'connections' => 31415],
            ['month' => 'Julio', 'short' => 'Jul', 'continuity' => 18.91770606249351, 'pressure' => 26.191924634070382, 'connections' => 32110],
        ];
    }
}
