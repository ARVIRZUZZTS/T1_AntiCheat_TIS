<?php

/**
 * @file    2026_09_27_000001_migracion_servidor_oficial.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-09-27
 *
 * @updated 2026-09-27
 *
 * @description
 * Esquema completo del dominio Tech One para el servidor oficial PostgreSQL.
 * Es la traducción ejecutable de `database/sql/001_schema.sql`, de modo que
 * el servidor remoto y la base de desarrollo queden con el mismo esquema sin
 * depender de phpPgAdmin ni de subir `.sql` a mano.
 *
 * Notas de diseño:
 * - Los ENUM de PostgreSQL se crean con DDL crudo porque `Blueprint` no los
 *   soporta de forma nativa (el `enum()` de Laravel es específico de MySQL).
 * - Todo es idempotente (`hasTable` / `hasEnumType`): se puede correr tanto
 *   contra una base vacía como contra una base que ya fue inicializada
 *   cargando los `.sql` a mano por phpPgAdmin.
 * - No se tocan las tablas de infraestructura de Laravel (`users`, `cache`,
 *   `jobs`, `sessions`, `password_reset_tokens`): son responsabilidad de las
 *   migraciones por defecto del framework. En el servidor, donde no hay
 *   terminal, se crean con `database/sql/003_tablas_laravel.sql`.
 *
 * @changelog
 * - 2026-09-27  [T1]  feat: migración inicial del esquema del servidor oficial.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<array{0: string, 1: list<string>}> Tipo de enum y sus valores.
     */
    private array $enums = [
        ['registrador_tipo', ['docente', 'auxiliar']],
        ['tipo_infraccion', ['tramposo', 'sospechoso', 'pendiente', 'aula equivocada']],
        ['estado_incidencia', ['Confirmado', 'Pendiente']],
        ['curso_estado', ['EnCurso', 'Finalizo']],
        ['nombre_tipo_gestion', ['Primer sem', 'inv', 'Seg sem', 'ver']],
        ['estado_notificacion', ['visto', 'recibido']],
        ['tipo_examen_nombre', ['PP', 'SP', 'FINAL', 'SI', 'PARCIAL', 'PRACTICA']],
        ['estudiante_examen_estado', ['habilitado', 'deshabilitado']],
        ['estado_usuario', ['Activo', 'Baja']],
        ['invitacion_estado', ['aceptado', 'rechazado', 'pendiente']],
        ['roles', ['docente', 'auxiliar']],
    ];

    /**
     * @var list<string> Tablas del dominio, en orden de dependencia.
     */
    private array $tables = [
        'usuario',
        'rol',
        'estudiante',
        'tipo_examen',
        'tipo_gestion',
        'ambiente',
        'norma',
        'material',
        'curso',
        'examen',
        'rol_usuario',
        'curso_tipo_gestion',
        'estudiante_curso',
        'auxiliar_curso',
        'examen_ambiente',
        'examen_norma',
        'examen_material_permitido',
        'examen_curso',
        'estudiante_examen',
        'estudiante_examen_ambiente',
        'registro_asistencia',
        'central_riesgo',
        'notificacion_docente',
        'notificacion_auxiliar',
        'invitacion_examen_compartido',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->enums as [$name, $values]) {
            $this->createEnum($name, $values);
        }

        // Tablas independientes
        $this->createTable('usuario', <<<'SQL'
            CREATE TABLE usuario (
              id_usuario     integer PRIMARY KEY,
              cod_sis        varchar(20) UNIQUE NOT NULL,
              password       varchar(100) NOT NULL,
              nombre_usuario varchar(50) NOT NULL,
              apellido       varchar(50) NOT NULL
            )
            SQL);

        $this->createTable('rol', <<<'SQL'
            CREATE TABLE rol (
              id_rol    integer PRIMARY KEY,
              nombre_rol roles NOT NULL
            )
            SQL);

        $this->createTable('estudiante', <<<'SQL'
            CREATE TABLE estudiante (
              sis_estudiante      varchar(20) PRIMARY KEY,
              nombre_estudiante   varchar(50) NOT NULL,
              apellido_estudiante varchar(50) NOT NULL,
              carrera             varchar(80)
            )
            SQL);

        $this->createTable('tipo_examen', <<<'SQL'
            CREATE TABLE tipo_examen (
              id_tipo_examen    integer PRIMARY KEY,
              nombre_tipo_examen tipo_examen_nombre NOT NULL
            )
            SQL);

        $this->createTable('tipo_gestion', <<<'SQL'
            CREATE TABLE tipo_gestion (
              id_tipo_gestion    integer PRIMARY KEY,
              nombre_tipo_gestion nombre_tipo_gestion NOT NULL
            )
            SQL);

        $this->createTable('ambiente', <<<'SQL'
            CREATE TABLE ambiente (
              id_ambiente    integer PRIMARY KEY,
              nombre_ambiente varchar(50) NOT NULL
            )
            SQL);

        $this->createTable('norma', <<<'SQL'
            CREATE TABLE norma (
              id_norma      integer PRIMARY KEY,
              detalle_norma varchar(255) NOT NULL
            )
            SQL);

        $this->createTable('material', <<<'SQL'
            CREATE TABLE material (
              id_material integer PRIMARY KEY,
              descripcion varchar(100) NOT NULL
            )
            SQL);

        // Tablas dependientes
        $this->createTable('curso', <<<'SQL'
            CREATE TABLE curso (
              id_curso       integer PRIMARY KEY,
              nombre_curso   varchar(100) NOT NULL,
              sis_doc        integer NOT NULL,
              fecha_creacion date,
              estado         curso_estado NOT NULL,
              CONSTRAINT fk_curso_docente FOREIGN KEY (sis_doc) REFERENCES usuario(id_usuario)
            )
            SQL);

        $this->createTable('examen', <<<'SQL'
            CREATE TABLE examen (
              id_examen   integer PRIMARY KEY,
              fecha       date,
              hora_inicio time,
              hora_fin    time,
              duracion    integer,
              creador     integer NOT NULL,
              tipo_examen integer NOT NULL,
              CONSTRAINT fk_examen_creador FOREIGN KEY (creador) REFERENCES usuario(id_usuario),
              CONSTRAINT fk_examen_tipo FOREIGN KEY (tipo_examen) REFERENCES tipo_examen(id_tipo_examen)
            )
            SQL);

        $this->createTable('rol_usuario', <<<'SQL'
            CREATE TABLE rol_usuario (
              id_usuario integer,
              id_rol     integer,
              PRIMARY KEY (id_usuario, id_rol),
              CONSTRAINT fk_ru_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
              CONSTRAINT fk_ru_rol     FOREIGN KEY (id_rol)     REFERENCES rol(id_rol)
            )
            SQL);

        $this->createTable('curso_tipo_gestion', <<<'SQL'
            CREATE TABLE curso_tipo_gestion (
              id_curso integer,
              id_tg    integer,
              PRIMARY KEY (id_curso, id_tg),
              CONSTRAINT fk_ctg_curso FOREIGN KEY (id_curso) REFERENCES curso(id_curso),
              CONSTRAINT fk_ctg_tg    FOREIGN KEY (id_tg)    REFERENCES tipo_gestion(id_tipo_gestion)
            )
            SQL);

        $this->createTable('estudiante_curso', <<<'SQL'
            CREATE TABLE estudiante_curso (
              sis_estudiante varchar(20),
              id_curso       integer,
              PRIMARY KEY (sis_estudiante, id_curso),
              CONSTRAINT fk_ec_estudiante FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante),
              CONSTRAINT fk_ec_curso      FOREIGN KEY (id_curso)       REFERENCES curso(id_curso)
            )
            SQL);

        $this->createTable('auxiliar_curso', <<<'SQL'
            CREATE TABLE auxiliar_curso (
              id_auxiliar integer,
              id_curso    integer,
              estado      estado_usuario NOT NULL,
              PRIMARY KEY (id_auxiliar, id_curso),
              CONSTRAINT fk_ac_auxiliar FOREIGN KEY (id_auxiliar) REFERENCES usuario(id_usuario),
              CONSTRAINT fk_ac_curso    FOREIGN KEY (id_curso)    REFERENCES curso(id_curso)
            )
            SQL);

        $this->createTable('examen_ambiente', <<<'SQL'
            CREATE TABLE examen_ambiente (
              id_examen   integer,
              id_ambiente integer,
              PRIMARY KEY (id_examen, id_ambiente),
              CONSTRAINT fk_ea_examen   FOREIGN KEY (id_examen)   REFERENCES examen(id_examen),
              CONSTRAINT fk_ea_ambiente FOREIGN KEY (id_ambiente) REFERENCES ambiente(id_ambiente)
            )
            SQL);

        $this->createTable('examen_norma', <<<'SQL'
            CREATE TABLE examen_norma (
              id_examen integer,
              id_norma  integer,
              PRIMARY KEY (id_examen, id_norma),
              CONSTRAINT fk_en_examen FOREIGN KEY (id_examen) REFERENCES examen(id_examen),
              CONSTRAINT fk_en_norma  FOREIGN KEY (id_norma)  REFERENCES norma(id_norma)
            )
            SQL);

        $this->createTable('examen_material_permitido', <<<'SQL'
            CREATE TABLE examen_material_permitido (
              id_examen             integer,
              id_material_permitido integer,
              PRIMARY KEY (id_examen, id_material_permitido),
              CONSTRAINT fk_emp_examen   FOREIGN KEY (id_examen)             REFERENCES examen(id_examen),
              CONSTRAINT fk_emp_material FOREIGN KEY (id_material_permitido) REFERENCES material(id_material)
            )
            SQL);

        $this->createTable('examen_curso', <<<'SQL'
            CREATE TABLE examen_curso (
              id_examen integer,
              id_curso  integer,
              PRIMARY KEY (id_examen, id_curso),
              CONSTRAINT fk_exc_examen FOREIGN KEY (id_examen) REFERENCES examen(id_examen),
              CONSTRAINT fk_exc_curso  FOREIGN KEY (id_curso)  REFERENCES curso(id_curso)
            )
            SQL);

        $this->createTable('estudiante_examen', <<<'SQL'
            CREATE TABLE estudiante_examen (
              id_estudiante_examen integer PRIMARY KEY,
              sis_estudiante       varchar(20) NOT NULL,
              id_examen            integer NOT NULL,
              estado               estudiante_examen_estado NOT NULL,
              motivo               varchar(255),
              modificado_por       integer,
              fecha_modificacion   timestamp,
              CONSTRAINT fk_ee_estudiante  FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante),
              CONSTRAINT fk_ee_examen      FOREIGN KEY (id_examen)      REFERENCES examen(id_examen),
              CONSTRAINT fk_ee_modificador FOREIGN KEY (modificado_por) REFERENCES usuario(id_usuario)
            )
            SQL);

        $this->createTable('estudiante_examen_ambiente', <<<'SQL'
            CREATE TABLE estudiante_examen_ambiente (
              id_ee       integer,
              id_ambiente integer,
              PRIMARY KEY (id_ee, id_ambiente),
              CONSTRAINT fk_eea_ee       FOREIGN KEY (id_ee)       REFERENCES estudiante_examen(id_estudiante_examen),
              CONSTRAINT fk_eea_ambiente FOREIGN KEY (id_ambiente) REFERENCES ambiente(id_ambiente)
            )
            SQL);

        $this->createTable('registro_asistencia', <<<'SQL'
            CREATE TABLE registro_asistencia (
              id_ingreso     integer PRIMARY KEY,
              hora_ingreso   time,
              id_examen      integer NOT NULL,
              id_estudiante  varchar(20) NOT NULL,
              id_registrador integer NOT NULL,
              CONSTRAINT fk_ra_examen      FOREIGN KEY (id_examen)      REFERENCES examen(id_examen),
              CONSTRAINT fk_ra_estudiante  FOREIGN KEY (id_estudiante)  REFERENCES estudiante(sis_estudiante),
              CONSTRAINT fk_ra_registrador FOREIGN KEY (id_registrador) REFERENCES usuario(id_usuario)
            )
            SQL);

        $this->createTable('central_riesgo', <<<'SQL'
            CREATE TABLE central_riesgo (
              id_registro       integer PRIMARY KEY,
              id_ingreso        integer NOT NULL,
              id_registrador    integer NOT NULL,
              detalle_motivo    varchar(255),
              fecha_registro    date,
              tipo_infraccion   tipo_infraccion NOT NULL,
              estado_incidencia estado_incidencia NOT NULL,
              CONSTRAINT fk_cr_ingreso     FOREIGN KEY (id_ingreso)     REFERENCES registro_asistencia(id_ingreso),
              CONSTRAINT fk_cr_registrador FOREIGN KEY (id_registrador) REFERENCES usuario(id_usuario)
            )
            SQL);

        $this->createTable('notificacion_docente', <<<'SQL'
            CREATE TABLE notificacion_docente (
              id_notificacion   integer PRIMARY KEY,
              id_central_riesgo integer NOT NULL,
              id_curso          integer NOT NULL,
              estado            estado_notificacion NOT NULL,
              CONSTRAINT fk_nd_central FOREIGN KEY (id_central_riesgo) REFERENCES central_riesgo(id_registro),
              CONSTRAINT fk_nd_curso   FOREIGN KEY (id_curso)          REFERENCES curso(id_curso)
            )
            SQL);

        $this->createTable('notificacion_auxiliar', <<<'SQL'
            CREATE TABLE notificacion_auxiliar (
              id_notificacion integer PRIMARY KEY,
              id_curso        integer NOT NULL,
              id_ambiente     integer NOT NULL,
              estado          estado_notificacion NOT NULL,
              CONSTRAINT fk_na_curso    FOREIGN KEY (id_curso)    REFERENCES curso(id_curso),
              CONSTRAINT fk_na_ambiente FOREIGN KEY (id_ambiente) REFERENCES ambiente(id_ambiente)
            )
            SQL);

        $this->createTable('invitacion_examen_compartido', <<<'SQL'
            CREATE TABLE invitacion_examen_compartido (
              id_invitacion       integer PRIMARY KEY,
              id_docente_invitado integer NOT NULL,
              id_examen           integer NOT NULL,
              estado              invitacion_estado NOT NULL,
              CONSTRAINT fk_iec_docente FOREIGN KEY (id_docente_invitado) REFERENCES usuario(id_usuario),
              CONSTRAINT fk_iec_examen  FOREIGN KEY (id_examen)           REFERENCES examen(id_examen)
            )
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::dropIfExists($table);
        }

        foreach (array_reverse($this->enums) as [$name]) {
            DB::statement("DROP TYPE IF EXISTS {$name}");
        }
    }

    /**
     * Crea un tipo ENUM de PostgreSQL si aún no existe.
     *
     * @param  list<string>  $values
     */
    private function createEnum(string $name, array $values): void
    {
        if ($this->hasEnumType($name)) {
            return;
        }

        $literals = implode(', ', array_map(
            fn (string $value): string => "'" . str_replace("'", "''", $value) . "'",
            $values
        ));

        DB::statement("CREATE TYPE {$name} AS ENUM ({$literals})");
    }

    /**
     * Crea una tabla si aún no existe.
     */
    private function createTable(string $table, string $ddl): void
    {
        if (Schema::hasTable($table)) {
            return;
        }

        DB::statement($ddl);
    }

    /**
     * Determina si un tipo ENUM ya existe en el esquema public.
     */
    private function hasEnumType(string $name): bool
    {
        $row = DB::selectOne(
            'SELECT 1 FROM pg_type WHERE typname::text = ? AND typtype::text = ?',
            [$name, 'e']
        );

        return $row !== null;
    }
};
