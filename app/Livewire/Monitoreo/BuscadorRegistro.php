<?php

/**
 * @file    BuscadorRegistro.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-01
 *
 * @updated 2026-10-01
 *
 * @description
 * Componente de página del buscador de registro: resuelve el examen en curso y
 * sus estudiantes con `GenerarReporteAsistenciaService` —los mismos que muestra
 * el monitor en vivo— y publica un índice de búsqueda con el que la vista filtra
 * en el navegador. Cada coincidencia expone solo el código SIS y el nombre, y su
 * enlace abre el formulario de incidencia ya existente (`RegistrarIncidencia`)
 * con el estudiante precargado por la URL.
 *
 * El filtrado NO se hace en PHP. Con `wire:model.live` cada tecla que se escribe
 * era un viaje completo al servidor (petición, render y diff del DOM), que es lo
 * que hacía el buscador lento por muy optimizado que estuviera el cálculo. Como
 * el índice es una colección pequeña y ya está resuelta, la lista se filtra en el
 * cliente y la respuesta es inmediata. La vista se encarga de eso; este
 * componente solo le publica el índice.
 *
 * @see  \App\Services\Monitoreo\GenerarReporteAsistenciaService
 * @see  \App\Livewire\Monitoreo\RegistrarIncidencia
 *
 * @changelog
 * - 2026-10-01  [Alex Candia]  feat: creación inicial del componente.
 * - 2026-10-01  [Alex Candia]  fix: deja de renderizarse como vista Blade plana
 *   desde el controlador —por eso `$busqueda` salía indefinida— y pasa a ser un
 *   componente Livewire de página completa, igual que `EstudiantesCurso`.
 * - 2026-10-01  [Alex Candia]  feat: se quita el atributo `#[Title]`: no alcanza el
 *   encabezado porque este componente delega el layout con `->extends('layouts.app')`
 *   y el título se declara en la vista, como en `RegistrarIncidencia`.
 * - 2026-10-01  [Alex Candia]  perf: el filtrado se mueve al navegador y el
 *   componente deja de recibir peticiones por tecla, que era el cuello de
 *   botella real. La búsqueda es un requisito crítico de la pantalla, así que se
 *   sacrifica el render en PHP a cambio de respuesta inmediata.
 * - 2026-10-01  [Alex Candia]  fix: el estado de búsqueda desaparece del componente.
 *   Con Alpine en la vista, `x-model` no enlazaba el input en este proyecto
 *   (solo se carga el bundle de Livewire), así que el término nunca llegaba al
 *   estado y el recuadro de resultados salía vacío. El filtrado quedó en
 *   JavaScript plano dentro de la vista, sin depender de ningún framework.
 * - 2026-10-01  [Alex Candia]  fix: se elimina la caché de sesión del índice. Un
 *   índice vacío cacheado sigue siendo un array, así que se reutilizaba para
 *   siempre y vaciaba el buscador; y no ahorraba trabajo relevante, porque el
 *   costo estaba en las peticiones por tecla y no en esta única lectura.
 */

namespace App\Livewire\Monitoreo;

use App\Models\Examen;
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BuscadorRegistro extends Component
{
    /**
     * Caracteres mínimos antes de filtrar, para no recorrer el índice con un
     * solo carácter pulsado.
     *
     * @var int
     */
    public const BUSQUEDA_MINIMO = 2;

    /**
     * Coincidencias mostradas como máximo.
     *
     * @var int
     */
    public const BUSQUEDA_LIMITE = 20;

    /**
     * Examen en curso, solo como id: es lo único del contexto que el formulario
     * de incidencia necesita de esta pantalla.
     *
     * @var int
     */
    public int $examenId = 0;

    /**
     * Índice de búsqueda del examen, ya normalizado en minúsculas para que el
     * navegador no tenga que componer cadenas ni aplicar toLowerCase por tecla.
     *
     * @var array<int, array{sis: string, nombre: string, termino: string}>
     */
    private array $indice = [];

    /**
     * Prepara el examen y su índice de búsqueda.
     *
     * @param  GenerarReporteAsistenciaService  $servicio
     * @return void
     */
    public function mount(GenerarReporteAsistenciaService $servicio): void
    {
        $examen = Examen::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id_examen')
            ->first();

        $this->examenId = (int) ($examen?->id_examen ?? 0);

        $this->indice = $examen === null
            ? []
            : $servicio->ejecutar($examen->id_examen)['estudiantes']
                ->map(fn (array $estudiante): array => [
                    'sis' => (string) ($estudiante['sis'] ?? ''),
                    'nombre' => trim(sprintf(
                        '%s %s',
                        $estudiante['nombre'] ?? '',
                        $estudiante['apellido'] ?? ''
                    )),
                    // Se normaliza una sola vez, al construir el índice, para que
                    // cada tecla solo tenga que comparar contra un texto ya en
                    // minúsculas en lugar de componer cadenas en el navegador.
                    'termino' => mb_strtolower(trim(sprintf(
                        '%s %s %s',
                        $estudiante['sis'] ?? '',
                        $estudiante['nombre'] ?? '',
                        $estudiante['apellido'] ?? ''
                    ))),
                ])
                ->values()
                ->all();
    }

    /**
     * Renderiza el buscador dentro del layout base de la aplicación.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.monitoreo.buscador-registro', [
            'indice' => $this->indice,
            'examenId' => $this->examenId,
        ])->extends('layouts.app');
    }
}