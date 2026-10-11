<?php

/**
 * @file    RegistrarIncidencia.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-10-10
 *
 * @description
 * Componente de página con el formulario de registro de una incidencia en la
 * central de riesgos. Precarga el estudiante y la materia que llegan desde el
 * monitor en vivo, permite reemplazarlos con el buscador de estudiantes de la
 * base de datos, escribir el estudiante a mano cuando no está en la base y
 * deriva el estado del registro del rol de quien lo realiza. Desde la central de
 * riesgo se abre sin datos precargados y el estudiante se busca a mano.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial del formulario desktop.
 * - 2026-09-26  [Valery D. Ortuno P]  feat: buscador de estudiantes contra la base
 *   de datos, motivos acordados por el equipo, estado a partir del rol recibido
 *   por la URL y una sola vista responsive de escritorio y móvil.
 * - 2026-09-26  [Amiddala]  fix: mount() recibía un parámetro `Rol $rol =
 *   Rol::DOCENTE` que ya no compila, porque `Rol` pasó de ser un enum a un
 *   modelo Eloquent sin ese caso/constante. Se quita el parámetro y la
 *   asignación duplicada; el rol se sigue leyendo únicamente de la URL (#68).
 * - 2026-09-28  [Candy]  feat: segunda entrada al formulario, la central de
 *   riesgo, que abre el formulario en blanco para que la persona busque al
 *   estudiante; el enlace de vuelta y el cancelar siguen a la pantalla de
 *   origen.
 * - 2026-09-28  [Valery D. Ortuno P]  fix: nombre y apellido por separado,
 *   motivos actualizados a los acordados por el equipo, materia precargada del
 *   monitor como solo lectura o texto libre con solo letras, números y espacios
 *   (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  fix: nombre, apellido y código SIS pasan a
 *   ser editables para poder registrar a un estudiante que no esté en la base,
 *   con validación de solo letras para el nombre y el apellido y de 9 dígitos
 *   para el código SIS (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  feat: aviso de código SIS duplicado cuando
 *   el campo se escribe a mano, comparando contra el valor precargado desde el
 *   monitor o elegido con la lupa para no bloquear esos dos caminos.
 * - 2026-09-28  [Valery D. Ortuno P]  fix: mensajes de validación propios para
 *   cada campo, porque la aplicación está en inglés y Laravel respondía "The
 *   nombre field is required" (#66).
 * - 2026-09-28  [Candy]  feat: al registrar se guarda la incidencia en
 *   `central_riesgo` con el estudiante, el usuario registrador, la materia, el
 *   estado, el motivo, la descripción y la fecha con hora, y se abre un modal de
 *   confirmación con el resumen de lo guardado, cuyo botón Aceptar termina el
 *   proceso y devuelve a la pantalla de origen (#70).
 * - 2026-09-29  [Candy]  feat: el registro se alinea con el esquema de
 *   `central_riesgo` que quedó tras el merge: se guarda `sis_estudiante`,
 *   `id_examen`, el motivo del enum `Motivo` y el detalle en `detalle_motivo`.
 *   La materia ya no es columna, sale del examen, y el modal muestra la del
 *   examen registrado. El código SIS tiene que existir en la base, porque la
 *   columna tiene llave foránea (#70).
 * - 2026-09-29  [Valery D. Ortuno P]  fix: el código SIS escrito a mano se da de
 *   alta al guardar en vez de rechazarse, porque reportar a un alumno que
 *   todavía no está cargado es un caso legítimo (#70).
 * - 2026-09-29  [Valery D. Ortuno P]  fix: el resumen ya no muestra quién
 *   registró. Todavía no hay login, así que lo que se guarda es siempre el
 *   usuario por defecto y el modal daba un nombre que no era el de quien
 *   estaba frente a la pantalla (#70).
 * - 2026-10-10  [T1]  feat: la materia de un examen compartido entre varios
 *   cursos se elige en un selector y el curso elegido se guarda en
 *   `central_riesgo.id_curso`; con un solo curso se fija solo. El alta deja de
 *   apoyarse en `id_ingreso` (columna que ya no existe) y calcula `id_registro`
 *   bloqueando la tabla, porque la tabla no tiene secuencia.
 * - 2026-10-10  [T1]  fix: la búsqueda de estudiantes ignora tildes y
 *   mayúsculas (la base guarda "López" y se busca "lopez"), porque el Postgres
 *   del proyecto no trae `unaccent`; se normaliza el término y el catálogo con
 *   `sinAcentos()` y se filtra en memoria.
 * - 2026-10-10  [T1]  perf: el buscador ya no consulta la base en cada tecla.
 *   El catálogo de estudiantes se trae una vez, se cachea unos minutos y se
 *   filtra en PHP, porque Supabase suma ~440 ms por consulta y el buscador se
 *   sentía lento. El alta de un estudiante limpia la caché.
 * - 2026-10-10  [T1]  fix: el formulario deja de validar el formato del nombre,
 *   del apellido y del código SIS (largo de 9 dígitos y solo letras). Esos
 *   datos vienen del registro de estudiantes y ya no son responsabilidad de
 *   este formulario; solo se exige que estén llenos y se pide buscarlos con la
 *   lupa cuando faltan.
 */
namespace App\Livewire\Monitoreo;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Rol;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
 
/**
 * Formulario de registro de incidencia contra un estudiante, invocado desde el
 * monitor del examen en curso.
 *
 * El monitor y el formulario viajan como página completa, así que el contexto
 * del registro (estudiante, materia y rol de quien reporta) llega por la URL en
 * lugar de por la sesión.
 *
 * @package  App\Livewire\Monitoreo
 * @author   Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @since    2026-09-25
 *
 * @see  \App\Enums\TipoInfraccion
 * @see  \App\Models\Estudiante
 * @see  \App\Models\Rol
 */
class RegistrarIncidencia extends Component
{
    /**
     * Caracteres admitidos en la descripción del hecho.
     *
     * @var int
     */
    public const DESCRIPCION_MAXIMO = 300;

    /**
     * Caracteres admitidos en el nombre y el apellido del estudiante, el mismo
     * ancho que sus columnas en la tabla `estudiante`.
     *
     * @var int
     */
    public const NOMBRE_MAXIMO = 50;

    /**
     * Caracteres admitidos en el código SIS, el mismo ancho que su columna en la
     * tabla `estudiante`. No se impone un largo fijo: el SIS lo define el
     * registro de estudiantes, no este formulario.
     *
     * @var int
     */
    public const SIS_MAXIMO = 20;

    /**
     * Clave de caché con el catálogo de estudiantes que usa el buscador.
     *
     * La base está en Supabase y cada consulta cuesta ~440 ms de ida y vuelta, así
     * que el catálogo se trae una vez y se filtra en memoria; sin esto el buscador
     * consulta la base en cada tecla.
     *
     * @var string
     */
    public const CACHE_ESTUDIANTES = 'incidencias.busqueda.estudiantes';

    /**
     * Minutos que el catálogo de estudiantes se mantiene en caché.
     *
     * Corto a propósito: si se da de alta un estudiante, como mucho tarda este
     * tiempo en aparecer en el buscador.
     *
     * @var int
     */
    public const CACHE_ESTUDIANTES_MINUTOS = 5;

    /**
     * Caracteres admitidos en la materia escrita a mano.
     *
     * @var int
     */
    public const MATERIA_MAXIMO = 100;
 
    /**
     * Caracteres mínimos antes de consultar estudiantes en la base de datos.
     *
     * @var int
     */
    public const BUSQUEDA_MINIMO = 2;

    /**
     * Estudiantes mostrados como máximo en el desplegable de resultados.
     *
     * @var int
     */
    public const BUSQUEDA_LIMITE = 8;

    /**
     * Caracteres acentuados que la búsqueda por nombre equipara a su versión
     * sin tilde, en mayúscula y minúscula. Es el mapa que aplica `sinAcentos()`
     * al término y a los campos, porque este Postgres no trae la extensión
     * `unaccent`.
     *
     * @var string
     */
    public const ACENTOS = 'áéíóúüñÁÉÍÓÚÜÑàèìòùÀÈÌÒÙ';

    /**
     * Reemplazo sin tilde de `ACENTOS`, del mismo largo, para `sinAcentos()`.
     *
     * @var string
     */
    public const SIN_ACENTOS = 'aeiouunAEIOUUNaeiouAEIOU';

    /**
     * Motivo que obliga a describir el hecho, porque no encaja en los demás.
     *
     * Es el valor del caso `Motivo::Otro`, que se escribe literal para no
     * arrastrar el enum a los tests.
     *
     * @var string
     */
    public const MOTIVO_OTRO = 'otro';

    /**
     * Pantalla desde la que se abrió el formulario.
     *
     * @var string
     */
    public const ORIGEN_MONITOREO = 'monitoreo';

    /**
     * Pantalla desde la que se abrió el formulario sin datos precargados: la
     * central de riesgo, donde el estudiante se busca a mano.
     *
     * @var string
     */
    public const ORIGEN_CENTRAL_RIESGO = 'central-riesgo';

    /**
     * Registrador usado cuando la pantalla de origen no envía ninguno.
     *
     * Es el primer usuario de `docker/postgres/init/002_seed_data.sql`. Existe
     * solo para que el registro se pueda guardar sin login; en cuanto la
     * aplicación tenga autenticación (#69) deja de usarse.
     *
     * @var int
     */
    public const USUARIO_POR_DEFECTO = 1;

    /**
     * Pantalla desde la que se abrió el formulario.
     *
     * @var string
     */
    public string $origen = '';

    /**
     * Rol con el que se registra la incidencia, recibido desde la pantalla que
     * abrió el formulario.
     *
     * @var string  Uno de los valores de \App\Models\Rol::NOMBRE_*.
     */
    public string $rol = Rol::NOMBRE_DOCENTE;

    /**
     * Usuario de la tabla `usuario` que queda como registrador de la incidencia.
     *
     * No hay autenticación en la aplicación, así que el id viaja por la URL junto
     * al rol, igual que este. Si no llega ninguno se usa el primer usuario
     * sembrado, para que la columna `id_registrador`, que es NOT NULL, nunca
     * deje la incidences sin poder guardar.
     *
     * @var int
     */
    public int $usuario = self::USUARIO_POR_DEFECTO;

    /**
     * Examen en el que se observó la incidencia, del que se deriva la materia.
     *
     * `central_riesgo.id_examen` es NOT NULL porque la materia ya no se guarda:
     * sale de `id_examen -> examen_curso -> curso.nombre_curso`. El monitor en
     * vivo, que sigue un solo examen, lo manda por la URL; desde la central de
     * riesgo, donde no hay un examen en curso, se resuelve con
     * `resolverExamen()` al validar.
     *
     * @var int
     */
    public int $idExamen = 0;

    /**
     * Curso elegido para la incidencia, solo cuando el examen se comparte entre
     * varios cursos.
     *
     * Con un examen de un solo curso se fija al único curso disponible y no se
     * pregunta. Con varios cursos se muestra un selector y este valor guarda el
     * elegido, que es el que se persiste en `central_riesgo.id_curso` y del que
     * sale la materia. Llega por la URL (`curso`) cuando la pantalla de origen
     * ya lo conoce.
     *
     * @var int
     */
    public int $idCurso = 0;

    /**
     * Texto escrito en el buscador de estudiantes.
     *
     * @var string
     */
    public string $busqueda = '';

    /**
     * Nombre del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $nombreEstudiante = '';

    /**
     * Apellido del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $apellidoEstudiante = '';
 
    /**
     * Código SIS del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $codigoSis = '';

    /**
     * Código SIS con el que se precargó el campo, para distinguir lo que llega de
     * una fuente confiable de lo que la persona escribe a mano.
     *
     * Es público a propósito: si fuera privado, Livewire lo reiniciaría a vacío en
     * cada petición posterior y se perdería la diferencia con el valor original.
     *
     * @var string
     */
    public string $sisPrecargado = '';
 
    /**
     * Materia del examen en el que se observa la anomalía, precargada desde el
     * monitor o escrita a mano cuando no viaja por la URL.
     *
     * @var string
     */
    public string $materia = '';
 
    /**
     * Motivo de la incidencia, con el valor de un caso de `Motivo`.
     *
     * @var string
     */
    public string $tipoIncidencia = '';

    /**
     * Detalle de lo ocurrido, con un máximo de `DESCRIPCION_MAXIMO` caracteres.
     *
     * Se guarda en `detalle_motivo`, que es la columna que la base reserva para
     * el texto libre del motivo.
     *
     * @var string
     */
    public string $descripcion = '';
 
    /**
     * Momento en que se abre el formulario, mostrado como dato de solo lectura.
     *
     * @var string
     */
    public string $fechaHoraRegistro = '';

    /**
     * Si el modal de confirmación ya está abierto, es decir, si la incidencia
     * quedó registrada y solo falta que la persona lo acepte para volver a la
     * pantalla de origen.
     *
     * @var bool
     */
    public bool $confirmacionVisible = false;

    /**
     * Copia de los datos validados en el momento del registro, para que el modal
     * muestre siempre lo que se acaba de guardar y no lo que el formulario
     * tuviera escrito después.
     *
     * @var array<string, string>
     */
    public array $resumen = [];
 
    /**
     * Precarga el estudiante, la materia y la fecha con lo que llega por la URL.
     *
     * El monitor es la única entrada que precarga datos: si el origen no es el
     * monitor —como pasa al entrar desde la central de riesgo— el formulario se
     * abre en blanco para que la persona busque al estudiante y escriba el
     * motivo. El rol también llega por la URL como texto (uno de `Rol::NOMBRE_*`),
     * porque `Rol` es un modelo de la tabla `rol` y no un enum con casos fijos.
     *
     * El id del examen llega siempre por la URL, venga del monitor o no, porque
     * la materia se deriva de él y `central_riesgo.id_examen` no admite null.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function mount(): void
    {
        $this->fechaHoraRegistro = now()->format('d/m/Y H:i');
        $this->origen = (string) request()->query('origen', '');
        $this->rol = (string) request()->query('rol', Rol::NOMBRE_DOCENTE);
        $this->usuario = (int) request()->query('usuario', self::USUARIO_POR_DEFECTO);
        $this->idExamen = (int) request()->query('examen', 0);
        $this->idCurso = (int) request()->query('curso', 0);
        $this->resolverCursoInicial();

        if ($this->origen !== self::ORIGEN_MONITOREO) {
            return;
        }

        [$nombre, $apellido] = $this->separarNombre((string) request()->query('nombre', ''));
        $this->nombreEstudiante = $nombre;
        $this->apellidoEstudiante = $apellido;
        $this->codigoSis = (string) request()->query('sis', '');
        $this->sisPrecargado = $this->codigoSis;
        $this->materia = (string) request()->query('materia', '');
    }

    /**
     * Pantalla a la que vuelve el formulario, según desde dónde se abrió.
     *
     * @return string  URL de la pantalla de origen.
     *
     * @author Candy
     * @since  2026-09-28
     */
    #[Computed]
    public function rutaVolver(): string
    {
        return match ($this->origen) {
            self::ORIGEN_CENTRAL_RIESGO => route('central-riesgo'),
            default => route('monitoreo'),
        };
    }

    /**
     * Texto del enlace que regresa a la pantalla desde la que se abrió el
     * formulario.
     *
     * @return string  Etiqueta del enlace de vuelta.
     *
     * @author Candy
     * @since  2026-09-28
     */
    #[Computed]
    public function etiquetaVolver(): string
    {
        return $this->origen === self::ORIGEN_CENTRAL_RIESGO
            ? 'Volver a la central de riesgo'
            : 'Volver al monitor en vivo';
    }

    /**
     * Divide el nombre completo que llega del monitor en nombre y apellido.
     *
     * El monitor envía "Nombres Apellidos" en un solo parámetro y el formulario
     * los muestra por separado; si la persona tiene más de una palabra, el resto
     * se acumula en el apellido.
     *
     * @param  string  $completo  Nombre recibido desde la pantalla de origen.
     * @return array{0: string, 1: string}  Nombre y apellido por separado.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-28
     */
    private function separarNombre(string $completo): array
    {
        // `explode` siempre devuelve al menos un elemento, así que solo el
        // segundo —el apellido— puede faltar cuando no hay espacio.
        $partes = explode(' ', trim($completo), 2);

        return [trim($partes[0]), trim($partes[1] ?? '')];
    }
    /**
     * Tipo de infracción con el que se persiste el registro, derivado del rol
     * de quien lo realiza: un docente confirma y un auxiliar deja el caso en
     * revisión.
     *
     * @return TipoInfraccion  Valor del enum `tipo_infraccion` de la base de datos.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function tipoInfraccion(): TipoInfraccion
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR
            ? TipoInfraccion::Sospechoso
            : TipoInfraccion::Tramposo;
    }

    /**
     * Etiqueta del estado con la que se muestra la incidencia en la pantalla.
     *
     * @return string  "Confirmado" para el docente, "En revisión" para el auxiliar.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-26
     */
    #[Computed]
    public function etiquetaEstado(): string
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR ? 'En revisión' : 'Confirmado';
    }

    /**
     * Estilo de la insignia del estado, tomado de la paleta que ya usa el
     * monitor en vivo.
     *
     * @return string  Clave de los tipos aceptados por `x-ui.badge`.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function tipoEstado(): string
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR
            ? 'status-en-revision'
            : 'status-central-riesgos';
    }
 
    /**
     * Los motivos disponibles para el selector de la vista, tomados del enum
     * `Motivo` para que el valor que se guarda y la etiqueta que se ve no se
     * dupliquen en la aplicación.
     *
     * @return array<string, string>  Motivos indexados por valor persistible.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    #[Computed]
    public function tiposIncidencia(): array
    {
        $motivos = [];

        foreach (Motivo::cases() as $motivo) {
            $motivos[$motivo->value] = $motivo->etiqueta();
        }

        return $motivos;
    }

    /**
     * Cursos del examen del registro, indexados por id y con su nombre como
     * etiqueta, para el selector de la vista.
     *
     * Sale de la relación `examen -> examen_curso -> curso`. Un examen de un
     * solo curso devuelve una entrada y la materia se muestra fija; varios
     * cursos devuelven varias y la persona elige. Si no llega examen o el examen
     * no tiene cursos, devuelve vacío y la materia se escribe a mano.
     *
     * @return array<int, string>  Cursos indexados por `id_curso`.
     *
     * @author T1
     * @since  2026-10-10
     */
    #[Computed]
    public function cursosDelExamen(): array
    {
        if ($this->idExamen <= 0) {
            return [];
        }

        $examen = Examen::query()->with('cursos')->find($this->idExamen);

        if ($examen === null) {
            return [];
        }

        return $examen->cursos
            ->sortBy('id_curso')
            ->mapWithKeys(fn (Curso $curso): array => [$curso->id_curso => $curso->nombre_curso])
            ->all();
    }

    /**
     * Fija el curso inicial del formulario al abrirlo.
     *
     * Con un solo curso se selecciona ese aunque no haya llegado por la URL, para
     * que el registro siempre quede con su curso. Con varios se respeta el que
     * venga por la URL solo si pertenece al examen; si no, queda vacío para
     * obligar a elegir.
     *
     * @return void
     *
     * @author T1
     * @since  2026-10-10
     */
    private function resolverCursoInicial(): void
    {
        $cursos = $this->cursosDelExamen();

        if ($cursos === []) {
            $this->idCurso = 0;

            return;
        }

        if (count($cursos) === 1) {
            $this->idCurso = (int) array_key_first($cursos);

            return;
        }

        if (! array_key_exists($this->idCurso, $cursos)) {
            $this->idCurso = 0;
        }
    }

    /**
     * Materia que se muestra en el formulario, ya sea la del curso fijado, la
     * del único curso del examen o la escrita a mano.
     *
     * @return string  Nombre de la materia mostrada.
     *
     * @author T1
     * @since  2026-10-10
     */
    #[Computed]
    public function materiaMostrada(): string
    {
        $cursos = $this->cursosDelExamen();

        if ($cursos !== []) {
            return (string) ($cursos[$this->idCurso] ?? reset($cursos));
        }

        return $this->materia;
    }

    /**
     * Si la materia se muestra fija, sin poder cambiarla en el formulario.
     *
     * Es el caso del examen de un solo curso, donde la materia sale del examen, y
     * el de la entrada del monitor que solo manda la materia como texto sin
     * examen. Con varios cursos NO es de solo lectura: se elige en el selector.
     * Sin ninguna referencia se escribe a mano.
     *
     * @return bool  Verdadero cuando la materia no se edita.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author T1
     * @since  2026-09-28
     */
    #[Computed]
    public function materiaEsSoloLectura(): bool
    {
        $cursos = $this->cursosDelExamen();

        if (count($cursos) === 1) {
            return true;
        }

        // Compatibilidad con la entrada del monitor que solo manda la materia
        // como texto y ningún examen: se muestra tal cual y no se edita.
        return $cursos === []
            && $this->origen === self::ORIGEN_MONITOREO
            && $this->materia !== '';
    }

    /**
     * Estudiantes que coinciden con lo escrito en el buscador, por nombre,
     * apellido, nombre y apellido juntos o código SIS.
     *
     * La búsqueda no distingue tildes ni mayúsculas: la base guarda "López" y
     * quien busca teclea "lopez" o "ANA" para encontrar "Ana". Como este
     * Postgres no trae `unaccent`, ambos lados se normalizan aquí con
     * `sinAcentos()`.
     *
     * El filtro recorre en memoria el catálogo cacheado en vez de consultar la
     * base en cada tecla: la base está en Supabase y cada consulta cuesta ~440
     * ms de ida y vuelta, así que se traen todos los estudiantes una sola vez.
     *
     * @return Collection<int, Estudiante>  Estudiantes encontrados, hasta el límite.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author T1
     * @since  2026-09-26
     */
    #[Computed]
    public function resultadosBusqueda(): Collection
    {
        $termino = trim($this->busqueda);

        if (mb_strlen($termino) < self::BUSQUEDA_MINIMO) {
            return new Collection();
        }

        $aguja = $this->sinAcentos($termino);

        return $this->catalogoEstudiantes()
            ->filter(fn (Estudiante $estudiante): bool => $this->coincide($estudiante, $aguja))
            ->take(self::BUSQUEDA_LIMITE)
            ->values();
    }

    /**
     * Indica si un estudiante coincide con el término ya normalizado.
     *
     * Se compara contra el SIS, el nombre, el apellido y el nombre completo
     * ("nombre apellido"), todos sin tildes ni mayúsculas.
     *
     * @param  Estudiante  $estudiante  Estudiante del catálogo.
     * @param  string      $aguja       Término buscado, ya normalizado.
     * @return bool  Verdadero si algún campo contiene el término.
     *
     * @author T1
     * @since  2026-10-10
     */
    private function coincide(Estudiante $estudiante, string $aguja): bool
    {
        $campos = [
            (string) $estudiante->sis_estudiante,
            (string) $estudiante->nombre_estudiante,
            (string) $estudiante->apellido_estudiante,
            $estudiante->nombre_estudiante.' '.$estudiante->apellido_estudiante,
        ];

        foreach ($campos as $campo) {
            if (str_contains($this->sinAcentos($campo), $aguja)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Catálogo de estudiantes que alimenta el buscador, cacheado unos minutos.
     *
     * Se cachea para no pagar el viaje a Supabase en cada tecla. El alta de un
     * estudiante nuevo limpia esta clave, así que el buscador no queda ciego
     * frente a un registro recién creado.
     *
     * @return Collection<int, Estudiante>  Estudiantes ordenados por nombre.
     *
     * @author T1
     * @since  2026-10-10
     */
    private function catalogoEstudiantes(): Collection
    {
        return Cache::remember(
            self::CACHE_ESTUDIANTES,
            now()->addMinutes(self::CACHE_ESTUDIANTES_MINUTOS),
            fn (): Collection => Estudiante::query()
                ->orderBy('nombre_estudiante')
                ->orderBy('apellido_estudiante')
                ->get(['sis_estudiante', 'nombre_estudiante', 'apellido_estudiante']),
        );
    }

    /**
     * Deja un texto sin tildes y en minúscula, con el mapa `ACENTOS`.
     *
     * @param  string  $texto  Texto a normalizar.
     * @return string  Texto sin tildes y en minúscula.
     *
     * @author T1
     * @since  2026-10-10
     */
    private function sinAcentos(string $texto): string
    {
        static $mapa = null;

        $mapa ??= array_combine(
            mb_str_split(self::ACENTOS),
            mb_str_split(self::SIN_ACENTOS),
        );

        return strtr(mb_strtolower($texto), $mapa);
    }

    /**
     * Caracteres escritos y límite permitido, para el contador de la descripción.
     *
     * @return string  Contador con el formato "usados / permitidos".
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    #[Computed]
    public function contadorDescripcion(): string
    {
        return mb_strlen($this->descripcion).' / '.self::DESCRIPCION_MAXIMO;
    }
 
    /**
     * Si la descripción debe rellenarse, lo que solo ocurre con el motivo
     * "Otro".
     *
     * @return bool  Verdadero cuando el motivo elegido es el que no se lista.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function descripcionEsObligatoria(): bool
    {
        return $this->tipoIncidencia === self::MOTIVO_OTRO;
    }

    /**
     * Refresca los resultados del buscador cuando se envía con el teclado.
     *
     * Los resultados se calculan en cada render, así que basta con volver a
     * validar la entrada para que la vista los muestre.
     *
     * @return void
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    public function buscarEstudiantes(): void
    {
        $this->resetErrorBag('busqueda');
    }

    /**
     * Reemplaza el estudiante del registro por el elegido en el buscador.
     *
     * @param  string  $sis  Código SIS del estudiante seleccionado.
     * @return void
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    public function seleccionarEstudiante(string $sis): void
    {
        $estudiante = Estudiante::query()->find($sis);

        if ($estudiante === null) {
            return;
        }

        $this->codigoSis = $estudiante->sis_estudiante;
        $this->sisPrecargado = $estudiante->sis_estudiante;
        $this->nombreEstudiante = $estudiante->nombre_estudiante;
        $this->apellidoEstudiante = $estudiante->apellido_estudiante;
        $this->busqueda = '';
        $this->resetErrorBag('codigoSis');
    }

    /**
     * Reglas de validación del formulario.
     *
     * @return array<string, array<int, mixed>>  Reglas por propiedad.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    protected function rules(): array
    {
        return [
            'nombreEstudiante' => [
                'required',
                'string',
                'max:'.self::NOMBRE_MAXIMO,
            ],
            'apellidoEstudiante' => [
                'required',
                'string',
                'max:'.self::NOMBRE_MAXIMO,
            ],
            'codigoSis' => $this->reglasCodigoSis(),
            'materia' => count($this->cursosDelExamen()) === 0
                ? [
                    'required',
                    'string',
                    'max:'.self::MATERIA_MAXIMO,
                    'regex:/^[\p{L}\p{N}\s]+$/u',
                ]
                : ['nullable'],
            'idCurso' => [
                function (string $atributo, mixed $valor, \Closure $fallar): void {
                    $cursos = $this->cursosDelExamen();

                    // Con varios cursos la persona tiene que elegir uno del
                    // examen; con uno solo lo fija el componente y no se pide.
                    if (count($cursos) > 1 && ! array_key_exists((int) $valor, $cursos)) {
                        $fallar('Seleccione la materia del examen.');
                    }
                },
            ],
            'tipoIncidencia' => ['required', Rule::in(array_keys($this->tiposIncidencia()))],
            'usuario' => ['required', 'integer', Rule::exists('usuario', 'id_usuario')],
            'descripcion' => [
                Rule::requiredIf(fn (): bool => $this->descripcionEsObligatoria()),
                'nullable',
                'string',
                'max:'.self::DESCRIPCION_MAXIMO,
            ],
        ];
    }

    /**
     * Reglas del código SIS, con el aviso de duplicado cuando el valor se
     * escribió a mano.
     *
     * El formato no se valida: el SIS lo define el registro de estudiantes, así
     * que este formulario acepta el valor tal como llega (de la lupa o del
     * monitor) y solo exige que no vaya vacío. El aviso de duplicado aplica
     * únicamente a lo que la persona teclea; el estudiante que llega precargado
     * desde el monitor y el que se elige con la lupa ya están ingresados en la
     * base, así que se compara el valor actual contra el precargado y el
     * duplicado se busca solo cuando difieren.
     *
     * @return array<int, mixed>  Reglas de validación del código SIS.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-28
     */
    private function reglasCodigoSis(): array
    {
        $reglas = ['required', 'string', 'max:'.self::SIS_MAXIMO];

        if ($this->codigoSis !== $this->sisPrecargado) {
            $reglas[] = Rule::unique('estudiante', 'sis_estudiante');
        }

        return $reglas;
    }

    /**
     * Mensajes de validación en el idioma de la interfaz.
     *
     * @return array<string, string>  Mensajes por regla.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    protected function validationAttributes(): array
    {
        return [
            'nombreEstudiante' => 'nombre',
            'apellidoEstudiante' => 'apellido',
            'codigoSis' => 'código SIS',
            'idCurso' => 'materia',
            'tipoIncidencia' => 'motivo',
        ];
    }
 
    /**
     * Mensajes de validación en el idioma de la interfaz.
     *
     * Todos los campos llevan su propio mensaje: la aplicación corre en inglés,
     * así que sin esto Laravel respondería "The nombre field is required".
     *
     * @return array<string, string>  Mensaje por regla incumplida.
     *
     * @author Amiddala
     * @since  2026-09-25
     */
    protected function messages(): array
    {
        return [
            'nombreEstudiante.required' => 'Busque y seleccione un estudiante de la lista.',
            'nombreEstudiante.max' => 'El nombre admite un máximo de '.self::NOMBRE_MAXIMO.' caracteres.',
            'apellidoEstudiante.required' => 'Busque y seleccione un estudiante de la lista.',
            'apellidoEstudiante.max' => 'El apellido admite un máximo de '.self::NOMBRE_MAXIMO.' caracteres.',
            'codigoSis.required' => 'Busque y seleccione un estudiante de la lista.',
            'codigoSis.max' => 'El código SIS admite un máximo de '.self::SIS_MAXIMO.' caracteres.',
            'codigoSis.unique' => 'Ese código SIS ya está registrado.',
            'materia.required' => 'Ingrese la materia del examen.',
            'materia.regex' => 'La materia solo puede contener letras, números y espacios.',
            'materia.max' => 'La materia admite un máximo de '.self::MATERIA_MAXIMO.' caracteres.',
            'tipoIncidencia.required' => 'Seleccione el motivo de la incidencia.',
            'tipoIncidencia.in' => 'Seleccione un motivo válido de la lista.',
            'usuario.required' => 'No se identificó al usuario que registra la incidencia.',
            'usuario.exists' => 'El usuario que registra la incidencia no existe.',
            'descripcion.required' => 'Describa el hecho observado.',
            'descripcion.max' => 'La descripción admite un máximo de '.self::DESCRIPCION_MAXIMO.' caracteres.',
        ];
    }
 
    /**
     * Registra la incidencia con los datos ingresados en el formulario y abre el
     * modal de confirmación con el resumen de lo guardado.
     *
     * La materia no es columna: se guarda el curso elegido cuando el examen se
     * comparte y `materia()` la resuelve, primero del curso guardado y si no del
     * primer curso del examen. El campo de materia sigue en pantalla porque es el
     * dato que la persona reconoce, pero el resumen del modal muestra la materia
     * real del registro.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException  Si falta el estudiante,
     *                                                       la materia o el motivo,
     *                                                       si el estudiante no
     *                                                       está en la base, si la
     *                                                       descripción es
     *                                                       obligatoria y está vacía,
     *                                                       o si excede el límite.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function registrar(): void
    {
        // Con el modal abierto la incidencia ya quedó registrada: un segundo
        // envío (doble clic, Enter repetido) no debe volver a pasar por aquí.
        if ($this->confirmacionVisible) {
            return;
        }

        $this->validate();

        /* El alta del estudiante y la incidencia van juntas: si la segunda
           falla, el primero tampoco debe quedar, o queda un alumno cargado sin
           ninguna incidencia que lo justifique. */
        $registro = DB::transaction(function (): CentralRiesgo {
            $estudiante = $this->resolverEstudiante();

            return CentralRiesgo::create([
                // La tabla no tiene secuencia: el id lo calcula la aplicación con
                // la tabla bloqueada, igual que el alta de examen.
                'id_registro' => $this->siguienteIdRegistro(),
                'sis_estudiante' => $estudiante->sis_estudiante,
                'id_examen' => $this->resolverExamen($estudiante),
                // Curso elegido cuando el examen se comparte; null cuando el
                // examen es de un solo curso (la materia igual sale del examen).
                'id_curso' => $this->idCursoParaGuardar(),
                'id_registrador' => $this->usuario,
                'motivo' => Motivo::from($this->tipoIncidencia),
                // La descripción es opcional con los motivos del catálogo, así que
                // vacía se guarda como null y no como cadena en blanco.
                'detalle_motivo' => $this->descripcion === '' ? null : $this->descripcion,
                'fecha_registro' => now(),
                'tipo_infraccion' => $this->tipoInfraccion(),
            ]);
        });

        $nombreEnBase = $registro->estudiante === null
            ? ''
            : trim($registro->estudiante->nombre_estudiante.' '.$registro->estudiante->apellido_estudiante);

        // El resumen no incluye quién registró: todavía no hay login, así que el
        // usuario guardado siempre es el por defecto y mostrarlo daría un nombre
        // que no es el de quien está frente a la pantalla (#70).
        $this->resumen = [
            'codigo' => (string) $registro->id_registro,
            'estudiante' => $nombreEnBase !== ''
                ? $nombreEnBase
                : trim($this->nombreEstudiante.' '.$this->apellidoEstudiante),
            'codigoSis' => (string) $registro->sis_estudiante,
            'materia' => (string) $registro->materia(),
            'motivo' => $registro->motivo->etiqueta(),
            'estado' => $this->etiquetaEstado(),
            'fechaHora' => $registro->fecha_registro->format('d/m/Y H:i'),
            'descripcion' => (string) $registro->detalle_motivo,
        ];

        $this->confirmacionVisible = true;
    }

    /**
     * Estudiante al que pertenece la incidencia, dado el código SIS escrito.
     *
     * Hay dos caminos distintos según de dónde venga el código. Del monitor o de
     * la lupa llega un estudiante que ya está en la base y se usa tal cual: el
     * nombre de un alumno cargado no se reescribe desde un reporte de incidencia.
     * Escrito a mano, la regla de validación ya garantiza que el código no está
     * en la base, así que hay que dar de alta a la persona:
     * `central_riesgo` tiene llave foránea contra `estudiante` y reportar a
     * alguien que todavía no está cargado es un caso legítimo, no un error.
     *
     * El alta no copia la carrera porque el formulario no la pide: un estudiante
     * que entra por acá no viene de una lista oficial y queda con la carrera en
     * null hasta que se concilie con la fuente (#70).
     *
     * @return Estudiante  Estudiante de la base, recien creado si no existia.
     */
    private function resolverEstudiante(): Estudiante
    {
        $estudiante = Estudiante::query()->firstOrCreate(
            ['sis_estudiante' => $this->codigoSis],
            [
                'nombre_estudiante' => $this->nombreEstudiante,
                'apellido_estudiante' => $this->apellidoEstudiante,
                'carrera' => null,
            ],
        );

        // El catálogo del buscador se cachea unos minutos; si se dio de alta un
        // estudiante, se descarta para que aparezca de inmediato.
        if ($estudiante->wasRecentlyCreated) {
            Cache::forget(self::CACHE_ESTUDIANTES);
        }

        return $estudiante;
    }

    /**
     * Examen al que se cuelga la incidencia, que es de donde sale la materia.
     *
     * `central_riesgo.id_examen` es NOT NULL, así que tiene que haber uno. Se
     * busca en este orden:
     *
     * 1. El que manda el monitor en vivo por la URL, que es el examen que está
     *    siguiendo en ese momento.
     * 2. El último examen en el que el estudiante tiene fila de asistencia,
     *    que es el examen del que viene la sospecha cuando la incidencia se
     *    reporta desde la central de riesgos.
     * 3. El primer examen disponible, para que el registro nunca se quede sin
     *    guardar por un dato que la persona no elige.
     *
     * @param  ?Estudiante  $estudiante  Estudiante del registro, si se encontró.
     * @return int  Id de un examen existente.
     */
    private function resolverExamen(?Estudiante $estudiante): int
    {
        $examen = $this->buscarExamen($this->idExamen);

        if ($examen === null && $estudiante !== null) {
            $examen = Examen::query()
                ->whereIn('id_examen', DB::table('registro_asistencia')
                    ->select('id_examen')
                    ->where('id_estudiante', $estudiante->sis_estudiante))
                ->orderByDesc('id_examen')
                ->first();
        }

        $examen ??= Examen::query()->orderBy('id_examen')->first();

        if ($examen === null) {
            // Sin ningún examen en la base no hay registro posible: la materia se
            // deriva del examen y la columna no admite null.
            throw ValidationException::withMessages([
                'materia' => 'No hay ningún examen registrado para asociar la incidencia.',
            ]);
        }

        return (int) $examen->id_examen;
    }

    /**
     * Examen con el id indicado, o null si no existe o no se indicó ninguno.
     *
     * @param  int  $idExamen  Id recibido por la URL.
     * @return ?Examen
     */
    private function buscarExamen(int $idExamen): ?Examen
    {
        if ($idExamen <= 0) {
            return null;
        }

        return Examen::query()->find($idExamen);
    }

    /**
     * Siguiente id del registro de la incidencia.
     *
     * `central_riesgo.id_registro` es un entero sin secuencia ni default, así que
     * el id lo calcula la aplicación. Se bloquea la tabla en modo
     * `SHARE ROW EXCLUSIVE` para que dos altas simultáneas no lean el mismo
     * máximo e intenten escribir el mismo id; el bloqueo se libera al terminar la
     * transacción del alta. Es el mismo criterio del alta de examen.
     *
     * @return int  Id libre para el nuevo registro.
     */
    private function siguienteIdRegistro(): int
    {
        DB::statement('LOCK TABLE central_riesgo IN SHARE ROW EXCLUSIVE MODE');

        return (int) CentralRiesgo::query()->max('id_registro') + 1;
    }

    /**
     * Curso que se guarda en `central_riesgo.id_curso`.
     *
     * Con un examen de varios cursos es el que la persona eligió; con uno solo es
     * ese mismo. Sin cursos (materia escrita a mano) queda null y `materia()`
     * cae al primer curso del examen o a null.
     *
     * @return ?int  Id del curso, o null si no se puede determinar.
     *
     * @author T1
     * @since  2026-10-10
     */
    private function idCursoParaGuardar(): ?int
    {
        $cursos = $this->cursosDelExamen();

        if ($cursos === []) {
            return null;
        }

        if (count($cursos) === 1) {
            return (int) array_key_first($cursos);
        }

        return array_key_exists($this->idCurso, $cursos) ? $this->idCurso : null;
    }

    /**
     * Cierra el modal de confirmación y devuelve a la persona a la pantalla desde
     * la que se abrió el formulario, dando por terminado el registro.
     *
     * @return RedirectResponse  Redirección a la pantalla de origen.
     *
     * @author Candy
     * @since  2026-09-28
     */
    public function aceptarRegistro(): RedirectResponse
    {
        $this->confirmacionVisible = false;

        return redirect()->to($this->rutaVolver());
    }
 
    /**
     * Cancela el registro y regresa a la pantalla desde la que se abrió.
     *
     * @return RedirectResponse  Redirección a la pantalla de origen.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function cancelar(): RedirectResponse
    {
        if ($this->origen === self::ORIGEN_MONITOREO) {
            return redirect()->route('monitoreo');
        }

        if ($this->origen === self::ORIGEN_CENTRAL_RIESGO) {
            return redirect()->route('central-riesgo');
        }

        return redirect()->back();
    }
 
    /**
     * Renderiza el formulario dentro del layout base de la aplicación.
     *
     * El título se declara como sección en la vista para que el layout lo muestre
     * tanto en el `<title>` del documento como en el encabezado.
     *
     * @return View  Vista del componente con el layout de la aplicación.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function render(): View
    {
        return view('livewire.monitoreo.registrar-incidencia')
            ->extends('layouts.app');
    }
}