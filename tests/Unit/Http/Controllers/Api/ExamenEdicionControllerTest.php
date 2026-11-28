<?php

/**
 * @file    ExamenEdicionControllerTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-24
 *
 * @updated 2026-11-24
 *
 * @description
 * Prueba unitaria del controlador de edicion de examen: cuando el servicio
 * detecta un conflicto de ambientes lanza una ValidationException y el
 * controlador debe responder 422 con los errores por campo, para que el modal
 * muestre el mensaje al lado del ambiente elegido.
 *
 * No levanta la aplicacion ni toca la base: el request y los servicios se
 * reemplazan por subclases de prueba, que es justo lo que se quiere verificar.
 *
 * @see  App\Http\Controllers\Api\ExamenEdicionController
 * @see  App\Services\Examen\ActualizarExamenService
 */

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\ExamenEdicionController;
use App\Http\Requests\UpdateExamenRequest;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Examen\ActualizarExamenService;
use App\Services\Examen\ObtenerExamenEdicionService;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Tests\TestCase;

class ExamenEdicionControllerTest extends TestCase
{
    public function test_un_conflicto_del_servicio_vuelve_como_422_con_errores(): void
    {
        $mensaje = 'El ambiente Aula 690B ya tiene un examen programado en ese horario.';
        $datos = [
            'tipo_examen' => 'examen primer parcial',
            'ambientes' => [5],
        ];
        $editor = new Usuario;
        $examen = new Examen;

        $validador = new Validator(new Translator(new ArrayLoader, 'en'), [], [], []);
        $validador->errors()->add('ambientes', $mensaje);

        $request = new class extends UpdateExamenRequest
        {
            /** @var array<string, mixed> */
            public array $datosValidados = [];

            public function validated($key = null, $default = null): array
            {
                return $this->datosValidados;
            }
        };
        $request->datosValidados = $datos;
        $request->setUserResolver(static fn (?string $guard = null): Usuario => $editor);

        $actualizar = new class($validador) extends ActualizarExamenService
        {
            public function __construct(private readonly Validator $validador) {}

            public function ejecutar(Examen $examen, array $datos, Usuario $editor): Examen
            {
                throw new ValidationException($this->validador);
            }
        };

        $obtenerDatos = new class extends ObtenerExamenEdicionService
        {
            public function ejecutar(Examen $examen): array
            {
                throw new \LogicException('El controlador no debe pedir datos despues de un error.');
            }
        };

        $respuesta = (new ExamenEdicionController)->actualizar(
            $request,
            $examen,
            $actualizar,
            $obtenerDatos,
        );

        $this->assertSame(422, $respuesta->getStatusCode());
        $this->assertSame([
            'message' => 'No se pudo guardar el examen.',
            'errors' => [
                'ambientes' => [$mensaje],
            ],
        ], $respuesta->getData(true));
    }
}
