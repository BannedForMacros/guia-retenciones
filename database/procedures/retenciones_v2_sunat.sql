-- =====================================================================
-- RETENCIONES v2 - Integracion DB Peru / SUNAT
-- - 5 columnas nuevas para almacenar respuesta del envio (CodigoHash, QR, pdf417...)
-- - Parametro id 12: SERIE_RETENCION (default R011)
-- - Estados nuevos: estadoproceso = P/C/F (Pendiente/Completado/Fallido)
--   estadosunat = NULL inicial, 'A' tras Aceptado SUNAT
-- - SP_RETENCION_INSERT actualizado con los nuevos defaults
-- - Nuevo SP_RETENCION_ACTUALIZAR_ENVIO_SUNAT
-- =====================================================================

-- USE guia_electronica;

-- ── 1. Columnas nuevas (idempotente) ──────────────────────────────────
SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retenciones'
      AND COLUMN_NAME = 'codigohash') = 0,
  'ALTER TABLE retenciones ADD COLUMN codigohash VARCHAR(100) NULL AFTER tramacdr',
  'SELECT 1'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retenciones'
      AND COLUMN_NAME = 'codigoqr') = 0,
  'ALTER TABLE retenciones ADD COLUMN codigoqr VARCHAR(500) NULL AFTER codigohash',
  'SELECT 1'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retenciones'
      AND COLUMN_NAME = 'pdf417') = 0,
  'ALTER TABLE retenciones ADD COLUMN pdf417 LONGTEXT NULL AFTER codigoqr',
  'SELECT 1'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retenciones'
      AND COLUMN_NAME = 'mensaje_error') = 0,
  'ALTER TABLE retenciones ADD COLUMN mensaje_error VARCHAR(1000) NULL AFTER pdf417',
  'SELECT 1'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'retenciones'
      AND COLUMN_NAME = 'respuesta_envio') = 0,
  'ALTER TABLE retenciones ADD COLUMN respuesta_envio LONGTEXT NULL AFTER mensaje_error',
  'SELECT 1'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── 2. Parametro id 12: SERIE_RETENCION ──────────────────────────────
INSERT INTO parametros (id, nombre, valor, created_at, updated_at)
VALUES (12, 'SERIE_RETENCION', 'R011', NOW(), NOW())
ON DUPLICATE KEY UPDATE valor = valor;

-- ── 3. Recrear SP_RETENCION_INSERT con nuevos defaults ────────────────
--      estadosunat = NULL (no enviado), estadoproceso = 'P', estadodocumento = NULL
DROP PROCEDURE IF EXISTS SP_RETENCION_INSERT;

DELIMITER $$

CREATE PROCEDURE SP_RETENCION_INSERT (
    IN p_rucempresa                  VARCHAR(11)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_serienumero                 VARCHAR(15)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_versionubl                  VARCHAR(3),
    IN p_versionestructura           VARCHAR(3),
    IN p_fechaemision                VARCHAR(10),
    IN p_numdocproveedor             VARCHAR(11),
    IN p_tipodocproveedor            VARCHAR(2),
    IN p_direccionproveedor          VARCHAR(150),
    IN p_razonsocialproveedor        VARCHAR(150),
    IN p_regimenretencion            VARCHAR(2),
    IN p_tasaretencion               VARCHAR(4),
    IN p_observacion                 VARCHAR(250),
    IN p_importetotalretenido        VARCHAR(15),
    IN p_monedaimportetotalretenido  VARCHAR(3),
    IN p_importetotalpagado          VARCHAR(15),
    IN p_monedaimportetotalpagado    VARCHAR(3),
    IN p_usuariocreador              VARCHAR(30)
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

    IF CHAR_LENGTH(IFNULL(p_rucempresa,'')) <> 11 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'rucempresa invalido (debe tener 11 digitos)';
    END IF;
    IF p_serienumero IS NULL OR p_serienumero = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'serienumero es obligatorio';
    END IF;

    SELECT COUNT(*) INTO v_existe FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La retencion ya existe para esta empresa y serie-numero';
    END IF;

    START TRANSACTION;

    INSERT INTO retenciones (
        rucempresa, serienumero, versionubl, versionestructura, fechaemision,
        numdocproveedor, tipodocproveedor, direccionproveedor, razonsocialproveedor,
        regimenretencion, tasaretencion, observacion,
        importetotalretenido, monedaimportetotalretenido,
        importetotalpagado, monedaimportetotalpagado,
        estadosunat, estadoproceso, estadodocumento,
        usuariocreador, fechacreacion,
        anio, mes, dia
    ) VALUES (
        p_rucempresa, p_serienumero, IFNULL(p_versionubl,'2.0'), IFNULL(p_versionestructura,'1.0'), p_fechaemision,
        p_numdocproveedor, IFNULL(p_tipodocproveedor,'06'), p_direccionproveedor, p_razonsocialproveedor,
        IFNULL(p_regimenretencion,'01'), p_tasaretencion, p_observacion,
        p_importetotalretenido, IFNULL(p_monedaimportetotalretenido,'PEN'),
        p_importetotalpagado,   IFNULL(p_monedaimportetotalpagado,'PEN'),
        NULL, 'P', NULL,                                                    -- ← nuevos defaults
        p_usuariocreador, NOW(),
        CAST(SUBSTRING(p_fechaemision,1,4) AS UNSIGNED),
        CAST(SUBSTRING(p_fechaemision,6,2) AS UNSIGNED),
        CAST(SUBSTRING(p_fechaemision,9,2) AS UNSIGNED)
    );

    COMMIT;
    SELECT p_rucempresa AS rucempresa, p_serienumero AS serienumero, 'OK' AS resultado;
END$$

-- ── 4. Nuevo SP_RETENCION_ACTUALIZAR_ENVIO_SUNAT ──────────────────────
DROP PROCEDURE IF EXISTS SP_RETENCION_ACTUALIZAR_ENVIO_SUNAT$$

CREATE PROCEDURE SP_RETENCION_ACTUALIZAR_ENVIO_SUNAT (
    IN p_rucempresa         VARCHAR(11)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_serienumero        VARCHAR(15)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_estadosunat        VARCHAR(2),
    IN p_estadoproceso      VARCHAR(2),
    IN p_estadodocumento    VARCHAR(2),
    IN p_codigohash         VARCHAR(100),
    IN p_codigoqr           VARCHAR(500),
    IN p_pdf417             LONGTEXT,
    IN p_mensaje_error      VARCHAR(1000),
    IN p_respuesta_envio    LONGTEXT,
    IN p_usuariomodificador VARCHAR(30)
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;

    SELECT COUNT(*) INTO v_existe FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe la retencion para actualizar envio SUNAT';
    END IF;

    UPDATE retenciones
       SET estadosunat        = p_estadosunat,
           estadoproceso      = p_estadoproceso,
           estadodocumento    = p_estadodocumento,
           codigohash         = p_codigohash,
           codigoqr           = p_codigoqr,
           pdf417             = p_pdf417,
           mensaje_error      = p_mensaje_error,
           respuesta_envio    = p_respuesta_envio,
           usuariomodificador = p_usuariomodificador,
           fechamodificacion  = NOW()
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    SELECT 'OK' AS resultado;
END$$

DELIMITER ;

-- ── 5. Verificacion ───────────────────────────────────────────────────
SHOW COLUMNS FROM retenciones LIKE 'codigohash';
SHOW COLUMNS FROM retenciones LIKE 'codigoqr';
SHOW COLUMNS FROM retenciones LIKE 'pdf417';
SHOW COLUMNS FROM retenciones LIKE 'mensaje_error';
SHOW COLUMNS FROM retenciones LIKE 'respuesta_envio';
SELECT id, nombre, valor FROM parametros WHERE id IN (11, 12);
