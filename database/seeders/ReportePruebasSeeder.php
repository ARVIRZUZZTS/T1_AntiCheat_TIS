<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportePruebasSeeder extends Seeder
{
    public function run(): void
    {
        // Datos de prueba separados del código de producción
        // Se pueden quitar truncando o removiendo este seeder

        // Examenes de Calculo I
        // 10: 2004-05-12 examen parcial (debe quedar SIN datos)
        // 7:  2024-07-17 examen parcial (con datos)

        $idExamenConDatos = 7;
        $idExamenSinDatos = 10;

        // Asegurar que examen 10 quede sin registros
        DB::table('registro_asistencia')->where('id_examen', $idExamenSinDatos)->delete();

        // Limpiar datos previos solo para este seeder (rango seguro)
        DB::table('registro_asistencia')
            ->where('id_examen', $idExamenConDatos)
            ->whereIn('id_estudiante', ['90600001', '90600002', '90600003', '90600004'])
            ->delete();

        DB::table('estudiante_examen_ambiente')
            ->whereIn('id_ee', function ($q) {
                $q->select('id_estudiante_examen')
                    ->from('estudiante_examen')
                    ->where('id_examen', 7)
                    ->whereIn('sis_estudiante', ['90600001', '90600002', '90600003', '90600004']);
            })
            ->delete();

        DB::table('estudiante_examen')
            ->where('id_examen', $idExamenConDatos)
            ->whereIn('sis_estudiante', ['90600001', '90600002', '90600003', '90600004'])
            ->delete();

        DB::table('estudiante')
            ->whereIn('sis_estudiante', ['90600001', '90600002', '90600003', '90600004'])
            ->delete();

        // Crear estudiantes con nombres y apellidos largos
        DB::table('estudiante')->insert([
            ['sis_estudiante' => '90600001', 'nombre_estudiante' => 'Maria Fernanda Alejandra', 'apellido_estudiante' => 'Gutierrez Condori Villarroel', 'carrera' => 'Ingenieria'],
            ['sis_estudiante' => '90600002', 'nombre_estudiante' => 'Luis Alberto Maximiliano', 'apellido_estudiante' => 'Quispe Mamani de la Torre', 'carrera' => 'Ingenieria'],
            ['sis_estudiante' => '90600003', 'nombre_estudiante' => 'Juan Carlos Eduardo', 'apellido_estudiante' => 'Perez Montano Arancibia', 'carrera' => 'Ingenieria'],
            ['sis_estudiante' => '90600004', 'nombre_estudiante' => 'Ana Sofia Valentina', 'apellido_estudiante' => 'Rojas Camacho Balderrama', 'carrera' => 'Ingenieria'],
        ]);

        // Inscribir en examen
        $eeIds = [];
        foreach (['90600001', '90600002', '90600003', '90600004'] as $sis) {
            $eeIds[$sis] = DB::table('estudiante_examen')->insert([
                'sis_estudiante' => $sis,
                'id_examen' => $idExamenConDatos,
                'estado' => 'habilitado',
            ]);
            $eeIds[$sis] = DB::table('estudiante_examen')
                ->where('sis_estudiante', $sis)
                ->where('id_examen', $idExamenConDatos)
                ->value('id_estudiante_examen');
        }

        // Asignar a aulas largas
        $ambienteIds = DB::table('ambiente')->pluck('id_ambiente')->take(2)->all();
        if (count($ambienteIds) === 2) {
            DB::table('estudiante_examen_ambiente')->insert([
                ['id_ee' => $eeIds['90600001'], 'id_ambiente' => $ambienteIds[0]],
                ['id_ee' => $eeIds['90600002'], 'id_ambiente' => $ambienteIds[0]],
                ['id_ee' => $eeIds['90600003'], 'id_ambiente' => $ambienteIds[1]],
                ['id_ee' => $eeIds['90600004'], 'id_ambiente' => $ambienteIds[1]],
            ]);
        }

        // Registrador
        $registrador = DB::table('usuario')->where('rol', 'docente')->value('sis_usuario') ?? '11111111';

        // Ingresos con horas variadas; uno sin salida (no se crea registro de salida aqui - solo hora_ingreso existe; hora_salida viene de otros campos? Revisar modelo)
        // Segun servicio: selecciona ra.hora_ingreso y setea hora_salida => null siempre. Pero vista muestra hora_salida desde estudiante.hora_salida? No, en vista usa estudiante.hora_salida del objeto.
        // En servicio se mapea hora_salida => null. Pero requerimiento dice "uno sin salida (se muestra "-")". Ya se muestra "-" si null/vacio. OK.
        DB::table('registro_asistencia')->insert([
            ['hora_ingreso' => '07:58:12', 'id_examen' => $idExamenConDatos, 'id_estudiante' => '90600001', 'id_registrador' => $registrador],
            ['hora_ingreso' => '08:05:43', 'id_examen' => $idExamenConDatos, 'id_estudiante' => '90600002', 'id_registrador' => $registrador],
            ['hora_ingreso' => '08:12:07', 'id_examen' => $idExamenConDatos, 'id_estudiante' => '90600003', 'id_registrador' => $registrador],
            ['hora_ingreso' => '08:27:26', 'id_examen' => $idExamenConDatos, 'id_estudiante' => '90600004', 'id_registrador' => $registrador],
        ]);
    }
}
