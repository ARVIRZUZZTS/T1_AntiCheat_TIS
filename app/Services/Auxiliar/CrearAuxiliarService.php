<?php

/**
 * @file    CrearAuxiliarService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Alta de un auxiliar: valida los duplicados, crea la cuenta con rol auxiliar
 * en estado habilitada dentro de una transacción, genera su contraseña y envía
 * las credenciales por correo.
 *
 *  - Tipo UMSS: `cod_sis` es el código SIS ingresado (único).
 *  - Tipo externo: `cod_sis` es un código interno `EXT-<id>` (no sirve para
 *    iniciar sesión) y el carnet es opcional (único cuando está presente).
 *
 * El acceso del auxiliar es por correo, así que el correo es obligatorio. La
 * contraseña se guarda con hash y se envía solo al auxiliar; el creador (y el
 * docente responsable si creó un coordinador) recibe un aviso sin la
 * contraseña. Si el correo con las credenciales no se puede enviar, la cuenta
 * igual queda creada.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (alta con tipo de auxiliar).
 *
 * @see  App\Services\Auxiliar\GeneradorContrasenaAuxiliarService
 * @see  App\Mail\CredencialesAuxiliarMail
 * @see  App\Mail\AuxiliarCreadoMail
 */

namespace App\Services\Auxiliar;

use App\Mail\AuxiliarCreadoMail;
use App\Mail\CredencialesAuxiliarMail;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Usuario\GeneradorNombreUsuarioService;
use App\Support\DTO\DatosNuevoAuxiliar;
use App\Support\DTO\ResultadoCreacionAuxiliar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CrearAuxiliarService
{
    public const MENSAJE_SIS_EN_USO = 'Ya existe un usuario con este código SIS.';

    public const MENSAJE_CARNET_EN_USO = 'Ya existe un usuario con este carnet.';

    public const MENSAJE_CORREO_EN_USO = 'Ya existe un usuario con este correo.';

    public function __construct(
        private readonly GeneradorContrasenaAuxiliarService $generadorContrasena,
        private readonly GeneradorNombreUsuarioService $generadorNombreUsuario,
    ) {
    }

    /**
     * Rechaza los datos si el SIS, el carnet o el correo ya están registrados.
     * Los mensajes van bajo las claves de los campos del formulario (`codSis`,
     * `carnet`, `correo`) para que la pantalla los marque.
     *
     * @throws ValidationException  Si algún dato ya existe.
     */
    public function revisarDuplicados(DatosNuevoAuxiliar $datos): void
    {
        $errores = [];

        if ($datos->esUmss() && $this->existeCodSis($datos->codSis)) {
            $errores['codSis'] = self::MENSAJE_SIS_EN_USO;
        }

        // El carnet es opcional para cualquier tipo, pero único cuando se ingresa.
        if ($datos->carnet !== '' && $this->existeCarnet($datos->carnet)) {
            $errores['carnet'] = self::MENSAJE_CARNET_EN_USO;
        }

        if ($this->existeCorreo($datos->correo)) {
            $errores['correo'] = self::MENSAJE_CORREO_EN_USO;
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Crea el auxiliar y le envía las credenciales por correo.
     *
     * @param  DatosNuevoAuxiliar  $datos    Datos ya normalizados del formulario.
     * @param  Usuario             $creador  Docente o coordinador que lo crea.
     *
     * @return ResultadoCreacionAuxiliar  El auxiliar creado y si el correo se envió.
     *
     * @throws ValidationException  Si el SIS, el carnet o el correo ya existen.
     */
    public function crear(DatosNuevoAuxiliar $datos, Usuario $creador): ResultadoCreacionAuxiliar
    {
        $contrasena = $this->generadorContrasena->generar($datos->nombre, $datos->apellido, $datos->documento());

        $auxiliar = DB::transaction(function () use ($datos, $creador, $contrasena): Usuario {
            // Serializa el alta para que el max(id)+1 y las unicidades no corran
            // contra otra alta simultánea.
            DB::statement('LOCK TABLE usuario IN SHARE ROW EXCLUSIVE MODE');

            $this->revisarDuplicados($datos);

            $id = ((int) Usuario::query()->max('id_usuario')) + 1;

            $usuario = new Usuario;
            $usuario->id_usuario = $id;
            $usuario->cod_sis = $datos->esUmss() ? $datos->codSis : 'EXT-'.$id;
            $usuario->correo = $datos->correo;
            $usuario->password = Hash::make($contrasena);
            // Usuario de acceso generado con prefijo de auxiliar (aux.nombre.apellido).
            $usuario->nombre_usuario = $this->generadorNombreUsuario->generar(
                $datos->nombre,
                $datos->apellido,
                GeneradorNombreUsuarioService::PREFIJO_AUXILIAR,
            );
            $usuario->nombre = $datos->nombre;
            $usuario->apellido = $datos->apellido;
            $usuario->tipo_auxiliar = $datos->tipoAuxiliar;
            // El carnet (cédula) es opcional para cualquier tipo.
            $usuario->carnet = $datos->carnet !== '' ? $datos->carnet : null;
            // Solo un docente otorga el permiso de coordinador; un coordinador no.
            $usuario->puede_crear_auxiliares = $creador->esDocente() && $datos->puedeCrearAuxiliares;
            $usuario->id_docente_responsable = $this->docenteResponsable($creador);
            $usuario->id_creador = (int) $creador->id_usuario;
            $usuario->estado_cuenta = 'habilitada';
            $usuario->fecha_creacion = now();
            $usuario->save();

            DB::table('rol_usuario')->insert([
                'id_usuario' => $usuario->id_usuario,
                'id_rol' => Rol::query()->where('nombre_rol', Rol::NOMBRE_AUXILIAR)->value('id_rol'),
            ]);

            return $usuario;
        });

        $credencialesEnviadas = $this->enviarCredenciales($auxiliar, $contrasena);
        $this->avisarCreador($auxiliar, $creador);

        return new ResultadoCreacionAuxiliar($auxiliar, $credencialesEnviadas);
    }

    /**
     * Docente al que queda relacionado el auxiliar: el propio creador si es
     * docente, o el docente responsable del coordinador que lo crea.
     */
    private function docenteResponsable(Usuario $creador): ?int
    {
        if ($creador->esDocente()) {
            return (int) $creador->id_usuario;
        }

        return $creador->id_docente_responsable !== null ? (int) $creador->id_docente_responsable : null;
    }

    /**
     * Envía las credenciales al auxiliar. Si el correo falla, la cuenta ya está
     * creada: se registra el error y se devuelve false para avisar que se
     * reenvíe desde la lista.
     */
    private function enviarCredenciales(Usuario $auxiliar, string $contrasena): bool
    {
        try {
            Mail::to((string) $auxiliar->correo)->send(new CredencialesAuxiliarMail($auxiliar, $contrasena));

            return true;
        } catch (\Throwable $error) {
            report($error);

            return false;
        }
    }

    /**
     * Avisa al creador (sin la contraseña) y, si el creador es coordinador,
     * también al docente responsable. Un fallo aquí no afecta el alta.
     */
    private function avisarCreador(Usuario $auxiliar, Usuario $creador): void
    {
        $destinatarios = [$creador->correo];

        if (! $creador->esDocente() && $auxiliar->id_docente_responsable !== null) {
            $destinatarios[] = Usuario::query()
                ->whereKey($auxiliar->id_docente_responsable)
                ->value('correo');
        }

        foreach (array_unique(array_filter($destinatarios)) as $correo) {
            try {
                Mail::to((string) $correo)->send(new AuxiliarCreadoMail($auxiliar));
            } catch (\Throwable $error) {
                report($error);
            }
        }
    }

    private function existeCodSis(string $codSis): bool
    {
        return Usuario::query()->where('cod_sis', $codSis)->exists();
    }

    private function existeCarnet(string $carnet): bool
    {
        return Usuario::query()->where('carnet', $carnet)->exists();
    }

    private function existeCorreo(string $correo): bool
    {
        return Usuario::query()->whereRaw('lower(btrim(correo)) = lower(?)', [$correo])->exists();
    }
}
