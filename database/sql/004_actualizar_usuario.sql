-- ============================================================================
--  TECH ONE / T1 - ACTUALIZACION DE `usuario` PARA BASAS YA CARGADAS
--  Motor: PostgreSQL
--  Subir vía: https://techone.tis.cs.umss.edu.bo/phppgadmin/  ->  SQL
--
--  ------------------------------------------------------------------
--  CUANDO USAR ESTE SCRIPT
--  ------------------------------------------------------------------
--  Este es el UNICO script que se sube a una base que ya tiene las 25
--  tablas (es decir, el servidor). 001/002/003 son solo para base vacia.
--
--  Hace tres cosas, en este orden:
--
--    1. Crea el ENUM `estado_incidencia` y la columna del mismo nombre en
--       `central_riesgo` (fix: el modelo la exigia y la base no la tenia).
--    2. Renombra la columna `contraseña` a `password`, para que `usuario`
--       sea la tabla de autenticacion sin depender del ENIE en el nombre.
--    3. Re-hashea las claves de los 5 usuarios de prueba, que estaban en
--       texto plano y por lo tanto no podian autenticarse.
--
--  ------------------------------------------------------------------
--  ES IDEMPOTENTE: podes correrlo las veces que quieras, no rompe nada.
--  ------------------------------------------------------------------
--  1. Desmarca "Paginar resultados" / "Paginate results" (si lo dejas
--     marcado, phpPgAdmin envuelve el script en un SELECT y falla).
-- ============================================================================


-- ============================================================================
--  1. ENUM + COLUMNA `estado_incidencia` EN `central_riesgo`
-- ============================================================================

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'estado_incidencia') THEN
    CREATE TYPE estado_incidencia AS ENUM ('Confirmado', 'Pendiente');
    RAISE NOTICE 'ENUM estado_incidencia creado';
  ELSE
    RAISE NOTICE 'ENUM estado_incidencia ya existia';
  END IF;
END
$$;

-- La columna es NOT NULL. Como la tabla ya tiene filas, se agrega con
-- DEFAULT para que el ALTER no falle, y despues se saca el DEFAULT para
-- que las nuevas inserciones tengan que declararlo (igual que en 001).
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = 'public' AND table_name = 'central_riesgo'
      AND column_name = 'estado_incidencia'
  ) THEN
    ALTER TABLE central_riesgo
      ADD COLUMN estado_incidencia estado_incidencia NOT NULL DEFAULT 'Pendiente';
    ALTER TABLE central_riesgo ALTER COLUMN estado_incidencia DROP DEFAULT;
    RAISE NOTICE 'columna central_riesgo.estado_incidencia creada';
  ELSE
    RAISE NOTICE 'columna central_riesgo.estado_incidencia ya existia';
  END IF;
END
$$;

-- Si la columna existia con DEFAULT, sacarselo aunque el paso anterior
-- la haya saltado.
ALTER TABLE central_riesgo ALTER COLUMN estado_incidencia DROP DEFAULT;


-- ============================================================================
--  2. RENOMBRAR `contraseña` -> `password`
-- ============================================================================

DO $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = 'public' AND table_name = 'usuario'
      AND column_name = 'contraseña'
  ) THEN
    ALTER TABLE usuario RENAME COLUMN "contraseña" TO password;
    RAISE NOTICE 'columna usuario.contraseña renombrada a usuario.password';
  ELSE
    RAISE NOTICE 'usuario.contraseña no existe (ya renombrada)';
  END IF;
END
$$;


-- ============================================================================
--  3. RE-HASHEAR LAS CLAVES DE PRUEBA
-- ============================================================================
--
--  Solo tiene efecto si las claves siguen en texto plano. Si ya estan
--  hasheadas, el UPDATE las sobreescribe con el mismo valor de prueba,
--  asi que sigue siendo seguro correrlo.
--
--  Claves resultantes:  DOC001/pass123  DOC002/pass456  DOC003/pass654
--                        AUX001/pass789  AUX002/pass321
-- ============================================================================

UPDATE usuario SET password = '$2y$10$14Gk6GUesrKoUC/44WPCceJCx/5VC.TebBFoxa9wZ8w2D6Hxtwj/K' WHERE cod_sis = 'DOC001';
UPDATE usuario SET password = '$2y$10$RdoblUmstY.7zO94vmrrBeNVAEI60IiMurhk.zQ7HvcpjEKryG4/.' WHERE cod_sis = 'DOC002';
UPDATE usuario SET password = '$2y$10$vE0SJjVzqLk40oLrhN2Kd.G8BETIyz5kmecym86oaELv8wZk8Ak9S' WHERE cod_sis = 'DOC003';
UPDATE usuario SET password = '$2y$10$p84V6RdlYDAkAs6dKobGUu2pK2RHarvLytnCxxCwC.WerJQJmLfwa' WHERE cod_sis = 'AUX001';
UPDATE usuario SET password = '$2y$10$oCKNmUI8S9rOkYZDe3r9LOyOU6Ouk4/YyRJvsJYdmYDnATSjK.76a' WHERE cod_sis = 'AUX002';


-- ============================================================================
--  VERIFICACION
-- ============================================================================

-- Debe devolver 5 filas, todas de 60 caracteres.
SELECT cod_sis, length(password) AS largo FROM usuario ORDER BY cod_sis;

-- Debe existir la columna (0 = todavia no).
SELECT COUNT(*) AS columnas_estado_incidencia
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'central_riesgo'
  AND column_name = 'estado_incidencia';

-- Debe existir `password` y NO existir `contraseña` (1 y 0).
SELECT
  COUNT(*) FILTER (WHERE column_name = 'password')     AS tiene_password,
  COUNT(*) FILTER (WHERE column_name = 'contraseña')   AS sigue_contrasena
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'usuario';
