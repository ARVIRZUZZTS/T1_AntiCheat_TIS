<?php

/**
 * @file    web.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-10
 * @updated 2026-10-11
 * @updated 2026-10-12
 * @updated 2026-10-21
 * @updated 2026-10-24
 * @updated 2026-10-25
 * @updated 2026-11-08
 * @updated 2026-11-14
 * @updated 2026-11-15
 * @updated 2026-11-16
 * @updated 2026-11-19
 *
 * @description
 * Definición de rutas web del panel. Incluye vistas estáticas y componentes Livewire.
 *
 * @changelog
 * - 2026-09-29  [David E. Chavez T.]  feat: rutas web del panel.
 * - 2026-10-01  [Alex Candia]  feat: añadida la pantalla de registro de ingreso.
 * - 2026-10-01  [Alex Candia]  refactor: la pantalla de registro de ingreso se
 *   enruta directo a su componente Livewire (`BuscadorRegistro`) en vez de pasar
 *   por `MonitoreoController`, con lo que el controlador deja de renderizar
 *   vistas de componentes y la ruta queda sin lógica.
 * - 2026-10-05  [Alex Candia]  feat: el detalle de la materia también entrega los
 *   exámenes del curso, para la pestaña Exámenes.
 * - 2026-10-05  [Alex Candia]  feat: el detalle entrega además el catálogo de
 *   ambientes, el de materiales y el de normas, que alimentan el modal de alta.
 * - 2026-10-05  [Alex Candia]  feat: endpoint POST de alta de examen, que es a
 *   donde envía el modal de la pestaña Exámenes.
 * - 2026-10-09  [Alisson D. Alvarado]  feat: ruta de la pantalla de
 *   notificaciones del docente. Por ahora renderiza una vista con datos
 *   mockeados; el endpoint real se conecta en una iteración posterior.
 * - 2026-10-09  [OchoaCesar]  refactor: la pestaña Exámenes deja de recibir los
 *   exámenes por la ruta; el parcial los consulta con fetch al endpoint
 *   GET /api/cursos/{idCurso}/examenes cuando se abre.
 * - 2026-10-09  [Alisson D. Alvarado]  refactor: la ruta de la central de
 *   riesgos pasa a ser una vista simple, ya que la pantalla es solo interfaz
 *   (datos mockeados) y llamaba a ListarAlertasService, que fallaba al leer
 *   `estado_incidencia`, columna aún no disponible en la base.
 * - 2026-10-10  [David E. Chavez T.]  refactor: el monitor en vivo se enruta
 *   directo a su componente Livewire (`MonitorEnVivo`) en vez de pasar por
 *   `MonitoreoController`; la pantalla ahora persiste el flujo de ingreso desde
 *   los modales y el controlador se elimina.
 * - 2026-10-10  [Valery D. Ortuno P]  docs: la central de riesgos ya no es un
 *   mock; su vista incrusta el componente Livewire ListaEstudiantes (HU 10).
 * - 2026-10-10  [Alisson D. Alvarado]  fix: la central de riesgos se enruta a
 *   su componente Livewire (`CentralRiesgo\Index`), que consume
 *   `ListarIncidenciasTramposoService` (el mismo listado del endpoint
 *   `/api/central-riesgo`) en vez de pintar datos mockeados.
 * - 2026-10-11  [OchoaCesar]  feat: ruta temporal /dev/login, registrada solo
 *   en APP_ENV=local, que inicia sesión con el usuario 1 para poder probar el
 *   modal de detalle de incidencia (la API exige sesión y aún no existe
 *   pantalla de login).
 * - 2026-10-12  [T1]  refactor: se elimina la pantalla intermedia de registro
 *   de ingreso (rutas /monitoreo/registro-ingreso y /buscador-incidencia): el
 *   monitor abre directo el formulario de incidencia, que ahora trae su propio
 *   buscador. Queda solo /registrar-incidencia.
 * - 2026-10-21  [Alex Candia]  feat: /reportes deja de ser vista mock. La ruta
 *   delega en ListarFiltrosReporteService, que entrega las materias y los
 *   exámenes agrupados por curso para los selectores dinámicos.
 * - 2026-10-24  [Valery D. Ortuno P]  refactor: "Materias" pasa a ser la
 *   sección Cursos (/cursos, /cursos/crear, /cursos/{curso}) y Exámenes deja
 *   de ser un mock (/examenes, /examenes/curso/{curso} y su /crear). Las
 *   closures de /materias se reemplazan por CursoController y ExamenController,
 *   y estas rutas quedan solo para docentes. El auxiliar tiene /mis-cursos.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: POST /cursos (`cursos.store`), alta
 *   de curso desde el formulario "Crear curso".
 * - 2026-10-24  [Valery D. Ortuno P]  feat: /central-riesgo queda solo para
 *   docentes (HU 10); antes un auxiliar podía abrirla por URL.
 * - 2026-10-25  [Valery D. Ortuno P]  refactor: se retira /registrar-incidencia;
 *   las incidencias se registran desde los modales del monitor en vivo (HU 5).
 * - 2026-10-25  [Alex Candia]  feat: /examenes pasa a ser una sola página con
 *   selector de materia; las rutas /examenes/curso/{curso} quedan por
 *   compatibilidad.
 * - 2026-11-08  [OchoaCesar]  refactor: se retira la ruta /docente/auxiliares
 *   (pestaña "Ver auxiliares"); los auxiliares se consultan desde /auxiliares.
 * - 2026-11-14  [Alex Candia]  feat: /examenes deja de filtrar por materia y
 *   usa el endpoint global /api/examenes; el curso del alta se elige en el
 *   formulario y las rutas por curso siguen por compatibilidad.
 * - 2026-11-15  [Alex Candia]  feat: "Ver curso" se divide en "Ver estudiantes" y
 *   "Habilitar estudiantes"; Reportes pasa a submenus (asistencia, horario de
 *   ingreso y intentos no autorizados) y /reportes redirige a asistencia.
 * - 2026-11-16  [Alex Candia]  feat: endpoints para eliminar un curso propio y
 *   consultar los auxiliares y estudiantes afectados antes de borrarlo; página
 *   "Eliminar curso" con la lista de cursos (sin modal todavía).
 * - 2026-11-19  [Valery D. Ortuno P]  feat: marcadores "Editar auxiliar" y
 *   "Eliminar auxiliar" (pagina en desarrollo) para el menu de Auxiliares, y
 *   pagina "Eliminar examen" (/examenes/eliminar).
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\Reporte\ExportarReporteIngresosIlegalesController;
use App\Http\Controllers\Reporte\ListarReporteIngresosIlegalesController;
use App\Http\Controllers\Reporte\ReporteController;
use App\Livewire\Cursos\ArchivarCurso;
use App\Livewire\Examenes\EstudiantesCurso;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Livewire\Monitoreo\AccesoMonitor;
use App\Livewire\Auxiliares\CrearAuxiliar;
use App\Livewire\Auxiliares\GestionAuxiliares;
use App\Livewire\Invitaciones\Index as InvitacionesIndex;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
Route::view('/', 'pages.inicio')->name('inicio');

Route::view('/inicio', 'pages.inicio');
// Destino de "Cursos" en el sidebar del auxiliar. Su contenido (cursos a los
// que está asignado y sus exámenes) se define en una HU posterior.
Route::view('/mis-cursos', 'pages.auxiliar-cursos')->name('auxiliar.cursos');
Route::get('/monitoreo', MonitorEnVivo::class)->name('monitoreo');
// Monitor en vivo · Ingreso: la vista de proximos examenes y el boton
// "Iniciar". Antes vivia en /monitoreo/acceso; la ruta vieja redirige.
Route::get('/monitoreo/ingreso', AccesoMonitor::class)->name('monitoreo.ingreso');
Route::redirect('/monitoreo/acceso', '/monitoreo/ingreso');

Route::get('/auxiliares', GestionAuxiliares::class)->name('auxiliares');
// Auxiliares · Asignar: elegir un auxiliar activo y asignarlo a un examen
// futuro. Requiere una tabla auxiliar_examen que aun no existe (Bloque 4),
// por eso de momento es un marcador.
Route::view('/auxiliares/asignar', 'pages.en-desarrollo', [
    'titulo' => 'Asignar auxiliar',
    'descripcion' => 'La asignacion de auxiliares a examenes esta en desarrollo.',
])->name('auxiliares.asignar');
// Auxiliares · Crear: el alta de auxiliar como pagina propia (antes era un
// modal dentro de GestionAuxiliares). Al guardar vuelve a la lista.
Route::get('/auxiliares/crear', CrearAuxiliar::class)->name('auxiliares.crear');
// Auxiliares · Editar / Eliminar: marcadores hasta que se implementen.
Route::view('/auxiliares/editar', 'pages.en-desarrollo', [
    'titulo' => 'Editar auxiliar',
    'descripcion' => 'La edicion de auxiliares esta en desarrollo.',
])->name('auxiliares.editar');
Route::view('/auxiliares/eliminar', 'pages.en-desarrollo', [
    'titulo' => 'Eliminar auxiliar',
    'descripcion' => 'La eliminacion de auxiliares esta en desarrollo.',
])->name('auxiliares.eliminar');

// Invitaciones: hoy un solo componente con pestanhas Enviar/Recibir. Se
// separa en dos subopciones; ambas sirven el mismo componente de momento y
// el Bloque 2 las divide en dos paginas. La ruta vieja /invitaciones redirige.
Route::redirect('/invitaciones', '/invitaciones/enviar');
// Mismo componente, dos paginas: el `tab` llega como default de ruta y fija la
// vista (Enviar o Aceptar invitacion), sin conmutador interno.
Route::get('/invitaciones/enviar', InvitacionesIndex::class)->defaults('tab', 'enviar')->name('invitaciones.enviar');
Route::get('/invitaciones/aceptar', InvitacionesIndex::class)->defaults('tab', 'recibir')->name('invitaciones.aceptar');

Route::view('/monitoreo/mock', 'livewire.monitoreo.mock')->name('monitoreo.mock');

    // Reportes: cada tipo es una subopcion del sidebar y muestra solo su
    // seccion, reutilizando la vista con los selectores compartidos.
    Route::redirect('/reportes', '/reportes/asistencia')->name('reportes');
    Route::get('/reportes/asistencia', [ReporteController::class, 'asistencia'])
        ->name('reportes.asistencia');
    Route::get('/reportes/horario-ingreso', [ReporteController::class, 'horarioIngreso'])
        ->name('reportes.horario-ingreso');
    Route::get('/reportes/intentos-no-autorizados', [ReporteController::class, 'intentosNoAutorizados'])
        ->name('reportes.intentos-no-autorizados');

Route::get('/reportes/ingresos-ilegales/exportar/{examen}', ExportarReporteIngresosIlegalesController::class)
    ->name('reportes.ingresos-ilegales.exportar');

Route::get('/reportes/asistencia/exportar/{examen}', [\App\Http\Controllers\Reporte\ExportarReporteAsistenciaController::class, 'exportar'])
    ->name('reportes.asistencia.exportar');

Route::get('/reportes/ingresos-ilegales', ListarReporteIngresosIlegalesController::class)
    ->name('reportes.ingresos-ilegales');
Route::view('/notificaciones', 'pages.notificaciones')->name('notificaciones');

// Destino de "Ver detalle" de las notificaciones (HU 12 · T4). La pantalla de
// detalle aún no existe: se deja la ruta lista con un placeholder navegable.
Route::get('/incidencias/{id}', fn (int $id) => view('pages.incidencia-detalle', ['id' => $id]))
    ->whereNumber('id')
    ->name('incidencias.detalle');
});

// Autenticación real (reemplaza a /dev/login).
Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'pages.login')->name('login');
    Route::view('/registro', 'pages.registro-docente')->name('registro');
    Route::view('/solicitud-revision', 'pages.solicitud-revision')->name('solicitud-revision');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'docente'])->group(function (): void {
    Route::view('/docentes', 'pages.docentes')->name('docentes');

    // Central de riesgos (HU 10): solo para docentes. La vista aporta el
    // layout; los datos los resuelve el componente Livewire ListaEstudiantes.
    // Se separa en dos subopciones (Incidencias / Sospechosos); de momento
    // ambas sirven la misma vista y el Bloque 2 recorta las pestanhas de cada
    // una. La ruta vieja /central-riesgo redirige a Incidencias.
    Route::redirect('/central-riesgo', '/central-riesgo/incidencias');
    Route::view('/central-riesgo/incidencias', 'pages.central-riesgo')->name('central-riesgo.incidencias');
    Route::view('/central-riesgo/sospechosos', 'pages.central-riesgo-sospechosos')->name('central-riesgo.sospechosos');

    // Sección Cursos: la lista, el formulario "Crear curso" (y su envío) y la
    // vista de cada curso con estudiantes y habilitaciones. Que el curso sea
    // del docente lo decide CursoPolicy en el controlador.
    Route::get('/cursos', [CursoController::class, 'index'])->name('cursos');
    Route::get('/cursos/crear', [CursoController::class, 'crear'])->name('cursos.crear');
    // Cursos · Ver estudiantes / Habilitar estudiantes: selector de curso +
    // la seccion correspondiente (el detalle conserva las dos pestañas).
    Route::get('/cursos/ver', fn (Request $request) => redirect()->route(
        'cursos.ver-estudiantes',
        $request->only('curso'),
    ))->name('cursos.ver');
    Route::get('/cursos/ver-estudiantes', [CursoController::class, 'verEstudiantes'])
        ->name('cursos.ver-estudiantes');
    Route::get('/cursos/habilitar-estudiantes', [CursoController::class, 'habilitarEstudiantes'])
        ->name('cursos.habilitar-estudiantes');
    Route::view('/cursos/editar', 'pages.en-desarrollo', [
        'titulo' => 'Editar curso',
        'descripcion' => 'La edicion de curso esta en desarrollo.',
    ])->name('cursos.editar');
    Route::get('/cursos/cargar-csv', function (Request $request) {
        $cursoId = $request->integer('curso');
        if ($cursoId > 0) {
            $curso = \App\Models\Curso::query()->findOrFail($cursoId);
            \Illuminate\Support\Facades\Gate::authorize('gestionar', $curso);
            app(\App\Services\Curso\AsegurarCursoEditableService::class)->asegurarCursoEditable($curso, 'curso');
        }

        return view('pages.en-desarrollo', [
            'titulo' => 'Cargar CSV',
            'descripcion' => 'La carga de estudiantes por CSV esta en desarrollo.',
        ]);
    })->name('cursos.cargar-csv');
    Route::get('/cursos/eliminar', [CursoController::class, 'eliminar'])->name('cursos.eliminar');
    Route::get('/cursos/archivar', ArchivarCurso::class)->name('cursos.archivar');
    Route::post('/cursos/{curso}/archivar', [CursoController::class, 'archivar'])
        ->whereNumber('curso')
        ->name('cursos.archivar.store');
    Route::get('/cursos/restaurar', [CursoController::class, 'restaurarLista'])->name('cursos.restaurar');
    Route::post('/cursos/{curso}/restaurar', [CursoController::class, 'restaurar'])
        ->whereNumber('curso')
        ->name('cursos.restaurar.store');
    Route::get('/archivados', [CursoController::class, 'archivados'])->name('cursos.archivados');
    Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');
    // Cursos · Eliminar curso: conteo previo para el modal y borrado definitivo.
    // Solo el docente dueño puede hacerlo (CursoPolicy::eliminar).
    Route::get('/cursos/{curso}/afectados', [CursoController::class, 'afectados'])
        ->whereNumber('curso')
        ->name('cursos.eliminar.afectados');
    Route::delete('/cursos/{curso}', [CursoController::class, 'destroy'])
        ->whereNumber('curso')
        ->name('cursos.destroy');
    Route::get('/cursos/{curso}', [CursoController::class, 'detalle'])
        ->whereNumber('curso')
        ->name('cursos.detalle');

    Route::get('/cursos/{curso}/estudiantes/estado', EstudiantesCurso::class)
        ->name('cursos.estudiantes.estado');
    // Sección Exámenes: una sola página con el listado global del docente y las
    // cuatro pestañas. Las rutas por curso se mantienen por compatibilidad.
    Route::get('/examenes', [ExamenController::class, 'index'])->name('examenes');
    Route::get('/examenes/curso/{curso}', [ExamenController::class, 'curso'])
        ->whereNumber('curso')
        ->name('examenes.curso');
    // Alta por curso (legado): se renombra a examenes.curso.crear para liberar
    // el nombre examenes.crear, que ahora usa la pagina nueva "Crear examen"
    // con selector de curso (Bloque 2). Se llega por URL directa.
    Route::get('/examenes/curso/{curso}/crear', [ExamenController::class, 'crear'])
        ->whereNumber('curso')
        ->name('examenes.curso.crear');

    // Examenes · Crear: selector de curso + el formulario de alta que ya
    // existe (reutiliza x-ui.form-crear-examen).
    Route::get('/examenes/crear', [ExamenController::class, 'crearFormulario'])->name('examenes.crear');
    // Examenes · Editar / Dividir: reutilizan el parcial de la seccion acotado
    // a su pestaña (selector de examen + formulario/edicion y division por API).
    Route::get('/examenes/editar', [ExamenController::class, 'editarVista'])->name('examenes.editar');
    Route::get('/examenes/dividir', [ExamenController::class, 'dividirVista'])->name('examenes.dividir');
    // Examenes · Eliminar: la lista con selector de curso y Eliminar por tarjeta.
    Route::get('/examenes/eliminar', [ExamenController::class, 'eliminarVista'])->name('examenes.eliminar');

    // Alta de examen desde su formulario. La validación vive en
    // StoreExamenRequest y el alta en RegistrarExamenService.
    Route::post('/cursos/{curso}/examenes', [ExamenController::class, 'store'])
        ->name('cursos.examenes.store');
});
