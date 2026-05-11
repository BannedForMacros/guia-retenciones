-- =============================================================================
--  Retenciones: soporte para BAJA via SUNAT (Resumen de Reversion)
-- -----------------------------------------------------------------------------
--  Endpoint DB Peru:  POST  http://e-dbfact.dbperu.com:8180/api/ResumenReversionCRE
--
--  Persistencia local (MySQL guia_electronica):
--    1) Nuevas columnas en `retenciones` para auditoria de la baja.
--    2) Tabla `retencion_correlativo_baja_diario` para reservar el correlativo
--       del IdDocumento "RR-YYYYMMDD-N" de forma ATOMICA por dia.
--
--  Compatible con MySQL 5.7 y 8.x. Idempotente: se puede correr varias veces.
-- =============================================================================

-- ── 1. Columnas en `retenciones` ────────────────────────────────────────────
--   Usamos un procedure temporal porque MySQL 5.7 no soporta
--   "ALTER TABLE ... ADD COLUMN IF NOT EXISTS". El procedure agrega solo las
--   columnas que aun no existen (consulta information_schema).

DROP PROCEDURE IF EXISTS _tmp_add_retencion_baja_cols;

DELIMITER $$
CREATE PROCEDURE _tmp_add_retencion_baja_cols()
BEGIN
  -- iddocumento_baja
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND COLUMN_NAME  = 'iddocumento_baja'
  ) THEN
    ALTER TABLE retenciones
      ADD COLUMN iddocumento_baja VARCHAR(40) NULL
        COMMENT 'RR-YYYYMMDD-N enviado a SUNAT'
        AFTER respuesta_envio;
  END IF;

  -- nro_ticket_baja
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND COLUMN_NAME  = 'nro_ticket_baja'
  ) THEN
    ALTER TABLE retenciones
      ADD COLUMN nro_ticket_baja VARCHAR(40) NULL
        COMMENT 'NroTicket devuelto por DB Peru'
        AFTER iddocumento_baja;
  END IF;

  -- nombre_archivo_baja
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND COLUMN_NAME  = 'nombre_archivo_baja'
  ) THEN
    ALTER TABLE retenciones
      ADD COLUMN nombre_archivo_baja VARCHAR(120) NULL
        COMMENT 'NombreArchivo devuelto por DB Peru'
        AFTER nro_ticket_baja;
  END IF;

  -- motivo_baja
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND COLUMN_NAME  = 'motivo_baja'
  ) THEN
    ALTER TABLE retenciones
      ADD COLUMN motivo_baja VARCHAR(100) NULL
        COMMENT 'Motivo informado a SUNAT (<=100 chars)'
        AFTER nombre_archivo_baja;
  END IF;

  -- fecha_envio_baja
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND COLUMN_NAME  = 'fecha_envio_baja'
  ) THEN
    ALTER TABLE retenciones
      ADD COLUMN fecha_envio_baja DATETIME NULL
        COMMENT 'Cuando se envio la baja a SUNAT'
        AFTER motivo_baja;
  END IF;

  -- respuesta_baja
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND COLUMN_NAME  = 'respuesta_baja'
  ) THEN
    ALTER TABLE retenciones
      ADD COLUMN respuesta_baja TEXT NULL
        COMMENT 'JSON raw de la respuesta de DB Peru'
        AFTER fecha_envio_baja;
  END IF;
END$$
DELIMITER ;

CALL _tmp_add_retencion_baja_cols();
DROP PROCEDURE _tmp_add_retencion_baja_cols;


-- ── 2. Tabla de correlativo diario ──────────────────────────────────────────
--   IdDocumento = "RR-YYYYMMDD-N", N reinicia cada dia.
--   El controller PHP reserva el siguiente valor atomicamente:
--
--     INSERT INTO retencion_correlativo_baja_diario (fecha, ultimo_valor)
--          VALUES (CURDATE(), 1)
--     ON DUPLICATE KEY UPDATE ultimo_valor = LAST_INSERT_ID(ultimo_valor + 1);
--     SELECT LAST_INSERT_ID();   -- correlativo a usar

CREATE TABLE IF NOT EXISTS retencion_correlativo_baja_diario (
  fecha         DATE         NOT NULL,
  ultimo_valor  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (fecha)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT = 'Correlativo diario para el IdDocumento RR-YYYYMMDD-N de las bajas de retencion';


-- ── 3. Indices utiles para soporte / reportes ───────────────────────────────
--   "CREATE INDEX IF NOT EXISTS" tampoco existe en 5.7, asi que mismo patron.

DROP PROCEDURE IF EXISTS _tmp_add_retencion_baja_idx;

DELIMITER $$
CREATE PROCEDURE _tmp_add_retencion_baja_idx()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND INDEX_NAME   = 'idx_retenciones_nro_ticket_baja'
  ) THEN
    CREATE INDEX idx_retenciones_nro_ticket_baja ON retenciones (nro_ticket_baja);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'retenciones'
       AND INDEX_NAME   = 'idx_retenciones_fecha_envio_baja'
  ) THEN
    CREATE INDEX idx_retenciones_fecha_envio_baja ON retenciones (fecha_envio_baja);
  END IF;
END$$
DELIMITER ;

CALL _tmp_add_retencion_baja_idx();
DROP PROCEDURE _tmp_add_retencion_baja_idx;


-- ── 4. Verificacion rapida (opcional) ───────────────────────────────────────
-- SHOW COLUMNS FROM retenciones LIKE '%baja%';
-- SHOW CREATE TABLE retencion_correlativo_baja_diario \G
-- SHOW INDEX FROM retenciones WHERE Key_name LIKE 'idx_retenciones%baja%';
