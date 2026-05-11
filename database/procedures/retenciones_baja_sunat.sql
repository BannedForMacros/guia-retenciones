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
--  No se crean nuevos SPs; el codigo PHP hace UPDATEs inline. SP_RETENCION_ANULAR
--  legacy se sigue invocando (baja logica local) DESPUES de que SUNAT confirme.
--
--  IDEMPOTENTE: usa IF NOT EXISTS / DROP IF EXISTS donde corresponde.
-- =============================================================================

-- ── 1. Columnas en `retenciones` ────────────────────────────────────────────
--    MySQL 8 soporta "ADD COLUMN IF NOT EXISTS"; si tu version es 5.7 usa el
--    bloque alternativo abajo (comentado) que chequea information_schema.

ALTER TABLE retenciones
  ADD COLUMN IF NOT EXISTS iddocumento_baja     VARCHAR(40)  NULL  COMMENT 'RR-YYYYMMDD-N enviado a SUNAT'  AFTER respuesta_envio,
  ADD COLUMN IF NOT EXISTS nro_ticket_baja      VARCHAR(40)  NULL  COMMENT 'NroTicket devuelto por DB Peru'  AFTER iddocumento_baja,
  ADD COLUMN IF NOT EXISTS nombre_archivo_baja  VARCHAR(120) NULL  COMMENT 'NombreArchivo devuelto por DB Peru' AFTER nro_ticket_baja,
  ADD COLUMN IF NOT EXISTS motivo_baja          VARCHAR(100) NULL  COMMENT 'Motivo informado a SUNAT (<=100 chars)' AFTER nombre_archivo_baja,
  ADD COLUMN IF NOT EXISTS fecha_envio_baja     DATETIME     NULL  COMMENT 'Cuando se envio la baja a SUNAT'  AFTER motivo_baja,
  ADD COLUMN IF NOT EXISTS respuesta_baja       TEXT         NULL  COMMENT 'JSON raw de la respuesta de DB Peru' AFTER fecha_envio_baja;

/*  Variante para MySQL 5.7 (no soporta IF NOT EXISTS en ADD COLUMN):
    Descomenta este bloque y comenta el ALTER de arriba.

DROP PROCEDURE IF EXISTS _tmp_add_col_baja;
DELIMITER $$
CREATE PROCEDURE _tmp_add_col_baja()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = 'retenciones'
                   AND COLUMN_NAME  = 'iddocumento_baja') THEN
    ALTER TABLE retenciones
      ADD COLUMN iddocumento_baja     VARCHAR(40)  NULL,
      ADD COLUMN nro_ticket_baja      VARCHAR(40)  NULL,
      ADD COLUMN nombre_archivo_baja  VARCHAR(120) NULL,
      ADD COLUMN motivo_baja          VARCHAR(100) NULL,
      ADD COLUMN fecha_envio_baja     DATETIME     NULL,
      ADD COLUMN respuesta_baja       TEXT         NULL;
  END IF;
END$$
DELIMITER ;
CALL _tmp_add_col_baja();
DROP PROCEDURE _tmp_add_col_baja;
*/


-- ── 2. Tabla de correlativo diario ──────────────────────────────────────────
--   El IdDocumento que pide SUNAT es "RR-YYYYMMDD-N" donde N reinicia cada dia.
--   Esta tabla guarda el ultimo correlativo emitido por fecha. El controller
--   reserva el siguiente valor con un solo statement atomico:
--
--     INSERT INTO retencion_correlativo_baja_diario (fecha, ultimo_valor)
--          VALUES (CURDATE(), 1)
--     ON DUPLICATE KEY UPDATE ultimo_valor = LAST_INSERT_ID(ultimo_valor + 1);
--     SELECT LAST_INSERT_ID();           -- <- devuelve el correlativo a usar
--
--   Asi evitamos race conditions sin necesidad de SELECT FOR UPDATE.

CREATE TABLE IF NOT EXISTS retencion_correlativo_baja_diario (
  fecha         DATE         NOT NULL,
  ultimo_valor  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (fecha)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT = 'Correlativo diario para el IdDocumento RR-YYYYMMDD-N de las bajas de retencion';


-- ── 3. Indice util para reportes / soporte ──────────────────────────────────
--    Para buscar "que retenciones se anularon con tal ticket / tal dia".

CREATE INDEX IF NOT EXISTS idx_retenciones_nro_ticket_baja
  ON retenciones (nro_ticket_baja);

CREATE INDEX IF NOT EXISTS idx_retenciones_fecha_envio_baja
  ON retenciones (fecha_envio_baja);


-- ── 4. Verificacion rapida ──────────────────────────────────────────────────
--    Descomenta para validar despues de correr el script:

-- SHOW COLUMNS FROM retenciones LIKE '%baja%';
-- SHOW CREATE TABLE retencion_correlativo_baja_diario \G
