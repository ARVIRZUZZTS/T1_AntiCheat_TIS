<?php

/**
 * @file RegistrarCuentaService.php
 * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @updated 2026-11-08
 * @updated 2026-11-09
 * @description Validación y registro de cuentas docentes.
 * @changelog
 * - 2026-11-08  [Alisson D. Alvarado]  feat: sugerir nombres de usuario disponibles.
 * - 2026-11-09  [Alisson D. Alvarado]  fix: distinguir conflictos y evitar correos como usuario.
 */

namespace App\Services\Auth;

use App\Enums\EstadoCuenta;
use App\Exceptions\RegistroDuplicadoException;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Docente\ListarSolicitudesRegistroService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrarCuentaService
{
    public const MENSAJE_NOMBRE_REQUERIDO = 'El nombre es requerido';

    public const MENSAJE_APELLIDO_REQUERIDO = 'El apellido es requerido';

    public const MENSAJE_USUARIO_REQUERIDO = 'El nombre de usuario es obligatorio';

    public const MENSAJE_USUARIO_EN_USO = 'Este nombre de usuario ya está en uso';

    public const MENSAJE_USUARIO_INVALIDO = 'El nombre de usuario no puede ser un correo electrónico';

    public const MENSAJE_CORREO_REQUERIDO = 'El correo es obligatorio';

    public const MENSAJE_CORREO_INVALIDO = 'Correo no válido';

    public const MENSAJE_CORREO_EN_USO = 'Este correo ya está registrado';

    public const MENSAJE_SIS_REQUERIDO = 'El código SIS es obligatorio';

    public const MENSAJE_SIS_EN_USO = 'Este código SIS ya está registrado';

    public const MENSAJE_CONTRASENA_REQUERIDA = 'La contraseña es requerida';

    public const MENSAJE_CONTRASENA_CORTA = 'La contraseña debe tener al menos 8 caracteres';

    public const MENSAJE_CONFIRMACION_REQUERIDA = 'La confirmación de contraseña es requerida';

    public const MENSAJE_CONTRASENAS_DISTINTAS = 'Las contraseñas no coinciden';

    private const INDICE_USUARIO = 'usuario_nombre_usuario_unique';

    /**
     * Propone un usuario disponible con apellidos y, si hace falta, un sufijo.
     *
     * @param string $nombre Nombre del docente.
     * @param string $apellido Apellidos del docente.
     * @return string Nombre de usuario disponible o vacío si faltan datos.
     * @throws \Illuminate\Database\QueryException Si falla la consulta.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     */
    public function sugerirNombreUsuario(string $nombre, string $apellido): string
    {
        $nombres = array_values(array_filter(explode(' ', Str::slug($nombre, ' '))));
        $apellidos = array_values(array_filter(explode(' ', Str::slug($apellido, ' '))));

        if ($nombres === [] || $apellidos === []) {
            return '';
        }

        $base = $nombres[0].'.'.$apellidos[0];
        $candidato = $base;

        foreach (array_slice($apellidos, 1) as $parte) {
            if (! $this->nombreUsuarioExiste($candidato)) {
                return $candidato;
            }

            $candidato .= '.'.$parte;
        }

        $base = $candidato;
        $numero = 2;
        while ($this->nombreUsuarioExiste($candidato)) {
            $candidato = $base.$numero++;
        }

        return $candidato;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, string>
     */
    public function normalizar(array $datos): array
    {
        return [
            'nombre' => $this->texto($datos['nombre'] ?? null),
            'apellido' => $this->texto($datos['apellido'] ?? null),
            'nombreUsuario' => $this->texto($datos['nombreUsuario'] ?? null),
            'correo' => $this->texto($datos['correo'] ?? null),
            'codigoSis' => $this->texto($datos['codigoSis'] ?? null),
            'contrasena' => $this->texto($datos['contrasena'] ?? null),
            'confirmarContrasena' => $this->texto($datos['confirmarContrasena'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, string>
     */
    public function validar(array $datos): array
    {
        $datos = $this->normalizar($datos);
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores['nombre'] = self::MENSAJE_NOMBRE_REQUERIDO;
        }

        if ($datos['apellido'] === '') {
            $errores['apellido'] = self::MENSAJE_APELLIDO_REQUERIDO;
        }

        if ($datos['nombreUsuario'] === '') {
            $errores['nombreUsuario'] = self::MENSAJE_USUARIO_REQUERIDO;
        } elseif (str_contains($datos['nombreUsuario'], '@')) {
            $errores['nombreUsuario'] = self::MENSAJE_USUARIO_INVALIDO;
        } elseif ($this->nombreUsuarioExiste($datos['nombreUsuario'])) {
            $errores['nombreUsuario'] = self::MENSAJE_USUARIO_EN_USO;
        }

        if ($datos['correo'] === '') {
            $errores['correo'] = self::MENSAJE_CORREO_REQUERIDO;
        } elseif (! preg_match('/^[^@\s]+@umss\.edu$/i', $datos['correo'])) {
            $errores['correo'] = self::MENSAJE_CORREO_INVALIDO;
        }

        if ($datos['codigoSis'] === '') {
            $errores['codigoSis'] = self::MENSAJE_SIS_REQUERIDO;
        }

        if ($datos['contrasena'] === '') {
            $errores['contrasena'] = self::MENSAJE_CONTRASENA_REQUERIDA;
        } elseif (mb_strlen($datos['contrasena']) < 8) {
            $errores['contrasena'] = self::MENSAJE_CONTRASENA_CORTA;
        }

        if ($datos['confirmarContrasena'] === '') {
            $errores['confirmarContrasena'] = self::MENSAJE_CONFIRMACION_REQUERIDA;
        } elseif (
            $datos['contrasena'] !== ''
            && $datos['contrasena'] !== $datos['confirmarContrasena']
        ) {
            $errores['confirmarContrasena'] = self::MENSAJE_CONTRASENAS_DISTINTAS;
        }

        return $errores;
    }

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws RegistroDuplicadoException
     */
    public function registrar(array $datos): Usuario
    {
        $datos = $this->normalizar($datos);

        $usuario = DB::transaction(function () use ($datos): Usuario {
            DB::statement('LOCK TABLE usuario IN SHARE ROW EXCLUSIVE MODE');

            if ($this->nombreUsuarioExiste($datos['nombreUsuario'])) {
                throw new RegistroDuplicadoException([
                    'nombreUsuario' => self::MENSAJE_USUARIO_EN_USO,
                ]);
            }

            $usuario = new Usuario;
            $usuario->id_usuario = ((int) Usuario::query()->max('id_usuario')) + 1;
            $usuario->cod_sis = $datos['codigoSis'];
            $usuario->nombre = $datos['nombre'];
            $usuario->apellido = $datos['apellido'];
            $usuario->nombre_usuario = $datos['nombreUsuario'];
            $usuario->correo = $datos['correo'];
            $usuario->password = Hash::make($datos['contrasena']);
            $usuario->fecha_creacion = now();
            // No ingresa hasta que dos docentes la aprueben (HU 26).
            $usuario->estado_cuenta = EstadoCuenta::Pendiente->value;

            try {
                $usuario->save();
            } catch (QueryException $error) {
                $errores = $this->erroresDeConflicto($error);

                if ($errores !== null) {
                    throw new RegistroDuplicadoException($errores);
                }

                throw $error;
            }

            // El registro solo crea docentes (los auxiliares los crea un docente).
            DB::table('rol_usuario')->insertUsing(
                ['id_usuario', 'id_rol'],
                DB::table('rol')->selectRaw('?, id_rol', [$usuario->id_usuario])->where('nombre_rol', Rol::NOMBRE_DOCENTE),
            );

            return $usuario;
        });

        // El contador de solicitudes del sidebar suma esta cuenta (HU 26).
        ListarSolicitudesRegistroService::olvidarPendientes();

        return $usuario;
    }

    private function nombreUsuarioExiste(string $nombreUsuario): bool
    {
        return Usuario::query()
            ->whereRaw('lower(btrim(nombre_usuario)) = lower(btrim(?))', [$nombreUsuario])
            ->exists();
    }

    /**
     * @return array<string, string>|null
     */
    private function erroresDeConflicto(QueryException $error): ?array
    {
        if ((string) $error->getCode() !== '23505') {
            return null;
        }

        // El mensaje completo de Laravel incluye el INSERT y todos sus campos, no solo el conflicto.
        $mensaje = $error->errorInfo[2] ?? '';

        if (str_contains($mensaje, self::INDICE_USUARIO) || str_contains($mensaje, '(nombre_usuario)')) {
            return ['nombreUsuario' => self::MENSAJE_USUARIO_EN_USO];
        }

        if (str_contains($mensaje, 'usuario_correo_unique') || str_contains($mensaje, '(correo)')) {
            return ['correo' => self::MENSAJE_CORREO_EN_USO];
        }

        if (str_contains($mensaje, 'cod_sis')) {
            return ['codigoSis' => self::MENSAJE_SIS_EN_USO];
        }

        return null;
    }

    private function texto(mixed $valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }
}
