-- =====================================================================
-- MODULO RETENCIONES - SCRIPT COMPLETO (TABLAS + SPs + PARAMETRO)
-- BD destino: guia_electronica (MySQL local del proyecto)
-- =====================================================================
-- Para ejecutar:
--   mysql -u root guia_electronica < retenciones.sql
-- =====================================================================

-- USE guia_electronica;

-- =====================================================================
-- TABLAS
-- =====================================================================

DROP TABLE IF EXISTS detalle_retenciones;
DROP TABLE IF EXISTS retenciones;

CREATE TABLE retenciones (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rucempresa                  VARCHAR(11)  NOT NULL,
    serienumero                 VARCHAR(15)  NOT NULL,
    versionubl                  VARCHAR(3)   DEFAULT '2.0',
    versionestructura           VARCHAR(3)   DEFAULT '1.0',
    fechaemision                VARCHAR(10)  NOT NULL,

    numdocproveedor             VARCHAR(11)  NOT NULL,
    tipodocproveedor            VARCHAR(2)   DEFAULT '06',
    direccionproveedor          VARCHAR(150) NULL,
    razonsocialproveedor        VARCHAR(150) NOT NULL,

    regimenretencion            VARCHAR(2)   DEFAULT '01',
    tasaretencion               VARCHAR(4)   NOT NULL,
    observacion                 VARCHAR(250) NULL,

    importetotalretenido        VARCHAR(15)  NOT NULL,
    monedaimportetotalretenido  VARCHAR(3)   DEFAULT 'PEN',
    importetotalpagado          VARCHAR(15)  NOT NULL,
    monedaimportetotalpagado    VARCHAR(3)   DEFAULT 'PEN',

    estadosunat                 VARCHAR(2)   DEFAULT '00',
    estadoproceso               VARCHAR(2)   DEFAULT '00',
    estadodocumento             VARCHAR(2)   DEFAULT '00',

    tramajson                   LONGTEXT     NULL,
    xmlfirmado                  LONGTEXT     NULL,
    tramacdr                    LONGTEXT     NULL,

    usuariocreador              VARCHAR(30)  NULL,
    fechacreacion               DATETIME     NULL,
    usuariomodificador          VARCHAR(30)  NULL,
    fechamodificacion           DATETIME     NULL,

    anio                        INT          NULL,
    mes                         INT          NULL,
    dia                         INT          NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ret_empresa_serie (rucempresa, serienumero),
    KEY idx_RET_proveedor    (rucempresa, numdocproveedor),
    KEY idx_RET_periodo      (rucempresa, anio, mes),
    KEY idx_RET_fechaemision (rucempresa, fechaemision),
    KEY idx_RET_estadosunat  (rucempresa, estadosunat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE detalle_retenciones (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rucempresa                  VARCHAR(11)  NOT NULL,
    serienumero                 VARCHAR(15)  NOT NULL,

    tipodocrelacionado          VARCHAR(2)   NOT NULL,
    serienumerorelacionado      VARCHAR(15)  NOT NULL,
    fechaemisiondocrelacionado  VARCHAR(10)  NULL,

    importetotaldocrela         VARCHAR(15)  NULL,
    monedaimportedocrela        VARCHAR(3)   DEFAULT 'PEN',

    fechapago                   VARCHAR(10)  NOT NULL,
    numeropago                  VARCHAR(3)   NOT NULL,
    importepagosinretencion     VARCHAR(15)  NOT NULL,
    monedapago                  VARCHAR(3)   DEFAULT 'PEN',

    importeretenido             VARCHAR(15)  NOT NULL,
    monedaimporteretenido       VARCHAR(3)   DEFAULT 'PEN',
    fecharetencion              VARCHAR(10)  NULL,

    montonetopagar              VARCHAR(15)  NULL,
    monedamontonetopagar        VARCHAR(3)   DEFAULT 'PEN',

    usuariocreador              VARCHAR(30)  NULL,
    fechacreacion               DATETIME     NULL,
    usuariomodificador          VARCHAR(30)  NULL,
    fechamodificacion           DATETIME     NULL,

    anio                        INT          NULL,
    mes                         INT          NULL,
    dia                         INT          NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_det_empresa_serie_pago (rucempresa, serienumero, numeropago),
    KEY idx_DETRET_docrela (rucempresa, serienumerorelacionado),
    KEY idx_DETRET_periodo (rucempresa, anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PARAMETRO: TASA DE RETENCION (id 11). Si tu tabla `parametros` tiene
-- otra columna en lugar de "valor" o "nombre", ajusta el INSERT.
-- =====================================================================

INSERT INTO parametros (id, nombre, valor, created_at, updated_at)
VALUES (11, 'TASA_RETENCION', '3.00', NOW(), NOW())
ON DUPLICATE KEY UPDATE valor = valor;

-- =====================================================================
-- LIMPIEZA PREVIA DE PROCEDIMIENTOS
-- =====================================================================

DROP PROCEDURE IF EXISTS SP_RETENCION_INSERT;
DROP PROCEDURE IF EXISTS SP_DETALLERETENCION_INSERT;
DROP PROCEDURE IF EXISTS SP_RETENCION_EXISTE;
DROP PROCEDURE IF EXISTS SP_RETENCION_OBTENER;
DROP PROCEDURE IF EXISTS SP_RETENCION_OBTENER_DETALLES;
DROP PROCEDURE IF EXISTS SP_RETENCION_LISTAR;
DROP PROCEDURE IF EXISTS SP_RETENCION_ACTUALIZAR_TRAMA;
DROP PROCEDURE IF EXISTS SP_RETENCION_ACTUALIZAR_ESTADO;
DROP PROCEDURE IF EXISTS SP_RETENCION_ANULAR;
DROP PROCEDURE IF EXISTS SP_RETENCION_REPORTE_PERIODO;
DROP PROCEDURE IF EXISTS SP_RETENCION_TOTALES_DASHBOARD;
DROP PROCEDURE IF EXISTS SP_RETENCION_SIGUIENTE_NUMERO;

DELIMITER $$

-- =====================================================================
-- 1) SP_RETENCION_INSERT
-- =====================================================================
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

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF CHAR_LENGTH(IFNULL(p_rucempresa,'')) <> 11 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'rucempresa invalido (debe tener 11 digitos)';
    END IF;

    IF p_serienumero IS NULL OR p_serienumero = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'serienumero es obligatorio';
    END IF;

    SELECT COUNT(*) INTO v_existe
      FROM retenciones
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
        '00', '00', '00',
        p_usuariocreador, NOW(),
        CAST(SUBSTRING(p_fechaemision,1,4) AS UNSIGNED),
        CAST(SUBSTRING(p_fechaemision,6,2) AS UNSIGNED),
        CAST(SUBSTRING(p_fechaemision,9,2) AS UNSIGNED)
    );

    COMMIT;

    SELECT p_rucempresa AS rucempresa, p_serienumero AS serienumero, 'OK' AS resultado;
END$$

-- =====================================================================
-- 2) SP_DETALLERETENCION_INSERT
-- =====================================================================
CREATE PROCEDURE SP_DETALLERETENCION_INSERT (
    IN p_rucempresa                  VARCHAR(11)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_serienumero                 VARCHAR(15)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_tipodocrelacionado          VARCHAR(2),
    IN p_serienumerorelacionado      VARCHAR(15),
    IN p_fechaemisiondocrelacionado  VARCHAR(10),
    IN p_importetotaldocrela         VARCHAR(15),
    IN p_monedaimportedocrela        VARCHAR(3),
    IN p_fechapago                   VARCHAR(10),
    IN p_numeropago                  VARCHAR(3),
    IN p_importepagosinretencion     VARCHAR(15),
    IN p_monedapago                  VARCHAR(3),
    IN p_importeretenido             VARCHAR(15),
    IN p_monedaimporteretenido       VARCHAR(3),
    IN p_fecharetencion              VARCHAR(10),
    IN p_montonetopagar              VARCHAR(15),
    IN p_monedamontonetopagar        VARCHAR(3),
    IN p_usuariocreador              VARCHAR(30)
)
BEGIN
    DECLARE v_cab INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT COUNT(*) INTO v_cab
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    IF v_cab = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe la cabecera de retencion (rucempresa + serienumero)';
    END IF;

    START TRANSACTION;

    INSERT INTO detalle_retenciones (
        rucempresa, serienumero, tipodocrelacionado, serienumerorelacionado, fechaemisiondocrelacionado,
        importetotaldocrela, monedaimportedocrela,
        fechapago, numeropago, importepagosinretencion, monedapago,
        importeretenido, monedaimporteretenido, fecharetencion,
        montonetopagar, monedamontonetopagar,
        usuariocreador, fechacreacion,
        anio, mes, dia
    ) VALUES (
        p_rucempresa, p_serienumero, p_tipodocrelacionado, p_serienumerorelacionado, p_fechaemisiondocrelacionado,
        p_importetotaldocrela, IFNULL(p_monedaimportedocrela,'PEN'),
        p_fechapago, p_numeropago, p_importepagosinretencion, IFNULL(p_monedapago,'PEN'),
        p_importeretenido, IFNULL(p_monedaimporteretenido,'PEN'), p_fecharetencion,
        p_montonetopagar, IFNULL(p_monedamontonetopagar,'PEN'),
        p_usuariocreador, NOW(),
        CAST(SUBSTRING(p_fechapago,1,4) AS UNSIGNED),
        CAST(SUBSTRING(p_fechapago,6,2) AS UNSIGNED),
        CAST(SUBSTRING(p_fechapago,9,2) AS UNSIGNED)
    );

    COMMIT;

    SELECT p_rucempresa AS rucempresa, p_serienumero AS serienumero,
           p_numeropago AS numeropago, 'OK' AS resultado;
END$$

-- =====================================================================
-- 3) SP_RETENCION_EXISTE
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_EXISTE (
    IN p_rucempresa  VARCHAR(11),
    IN p_serienumero VARCHAR(15)
)
BEGIN
    SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS existe
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;
END$$

-- =====================================================================
-- 4) SP_RETENCION_OBTENER
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_OBTENER (
    IN p_rucempresa  VARCHAR(11),
    IN p_serienumero VARCHAR(15)
)
BEGIN
    SELECT
        id, rucempresa, serienumero, versionubl, versionestructura, fechaemision,
        numdocproveedor, tipodocproveedor, direccionproveedor, razonsocialproveedor,
        regimenretencion, tasaretencion, observacion,
        importetotalretenido, monedaimportetotalretenido,
        importetotalpagado, monedaimportetotalpagado,
        estadosunat, estadoproceso, estadodocumento,
        usuariocreador, fechacreacion, usuariomodificador, fechamodificacion,
        anio, mes, dia
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;
END$$

-- =====================================================================
-- 5) SP_RETENCION_OBTENER_DETALLES
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_OBTENER_DETALLES (
    IN p_rucempresa  VARCHAR(11),
    IN p_serienumero VARCHAR(15)
)
BEGIN
    SELECT
        id, rucempresa, serienumero, tipodocrelacionado, serienumerorelacionado,
        fechaemisiondocrelacionado, importetotaldocrela, monedaimportedocrela,
        fechapago, numeropago, importepagosinretencion, monedapago,
        importeretenido, monedaimporteretenido, fecharetencion,
        montonetopagar, monedamontonetopagar,
        usuariocreador, fechacreacion, usuariomodificador, fechamodificacion,
        anio, mes, dia
      FROM detalle_retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero
     ORDER BY CAST(numeropago AS UNSIGNED);
END$$

-- =====================================================================
-- 6) SP_RETENCION_LISTAR
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_LISTAR (
    IN p_rucempresa       VARCHAR(11),
    IN p_fecha_desde      VARCHAR(10),
    IN p_fecha_hasta      VARCHAR(10),
    IN p_numdocproveedor  VARCHAR(11),
    IN p_estadosunat      VARCHAR(2),
    IN p_search           VARCHAR(100),
    IN p_limit            INT,
    IN p_offset           INT
)
BEGIN
    DECLARE v_limit  INT DEFAULT 50;
    DECLARE v_offset INT DEFAULT 0;

    IF p_limit  IS NOT NULL AND p_limit  > 0 THEN SET v_limit  = p_limit;  END IF;
    IF p_offset IS NOT NULL AND p_offset >= 0 THEN SET v_offset = p_offset; END IF;

    SELECT
        id, rucempresa, serienumero, fechaemision,
        numdocproveedor, razonsocialproveedor,
        regimenretencion, tasaretencion,
        importetotalretenido, monedaimportetotalretenido,
        importetotalpagado,   monedaimportetotalpagado,
        estadosunat, estadoproceso, estadodocumento,
        fechacreacion
      FROM retenciones
     WHERE rucempresa = p_rucempresa
       AND ( p_fecha_desde     IS NULL OR p_fecha_desde     = '' OR fechaemision >= p_fecha_desde )
       AND ( p_fecha_hasta     IS NULL OR p_fecha_hasta     = '' OR fechaemision <= p_fecha_hasta )
       AND ( p_numdocproveedor IS NULL OR p_numdocproveedor = '' OR numdocproveedor = p_numdocproveedor )
       AND ( p_estadosunat     IS NULL OR p_estadosunat     = '' OR estadosunat = p_estadosunat )
       AND ( p_search          IS NULL OR p_search          = ''
             OR serienumero          LIKE CONCAT('%', p_search, '%')
             OR razonsocialproveedor LIKE CONCAT('%', p_search, '%')
             OR numdocproveedor      LIKE CONCAT('%', p_search, '%') )
     ORDER BY fechaemision DESC, serienumero DESC
     LIMIT v_limit OFFSET v_offset;

    SELECT COUNT(*) AS total
      FROM retenciones
     WHERE rucempresa = p_rucempresa
       AND ( p_fecha_desde     IS NULL OR p_fecha_desde     = '' OR fechaemision >= p_fecha_desde )
       AND ( p_fecha_hasta     IS NULL OR p_fecha_hasta     = '' OR fechaemision <= p_fecha_hasta )
       AND ( p_numdocproveedor IS NULL OR p_numdocproveedor = '' OR numdocproveedor = p_numdocproveedor )
       AND ( p_estadosunat     IS NULL OR p_estadosunat     = '' OR estadosunat = p_estadosunat )
       AND ( p_search          IS NULL OR p_search          = ''
             OR serienumero          LIKE CONCAT('%', p_search, '%')
             OR razonsocialproveedor LIKE CONCAT('%', p_search, '%')
             OR numdocproveedor      LIKE CONCAT('%', p_search, '%') );
END$$

-- =====================================================================
-- 7) SP_RETENCION_ACTUALIZAR_TRAMA
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_ACTUALIZAR_TRAMA (
    IN p_rucempresa         VARCHAR(11),
    IN p_serienumero        VARCHAR(15),
    IN p_tramajson          LONGTEXT,
    IN p_xmlfirmado         LONGTEXT,
    IN p_usuariomodificador VARCHAR(30)
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;

    SELECT COUNT(*) INTO v_existe
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe la retencion';
    END IF;

    UPDATE retenciones
       SET tramajson           = p_tramajson,
           xmlfirmado          = p_xmlfirmado,
           usuariomodificador  = p_usuariomodificador,
           fechamodificacion   = NOW()
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    SELECT 'OK' AS resultado;
END$$

-- =====================================================================
-- 8) SP_RETENCION_ACTUALIZAR_ESTADO
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_ACTUALIZAR_ESTADO (
    IN p_rucempresa         VARCHAR(11),
    IN p_serienumero        VARCHAR(15),
    IN p_estadosunat        VARCHAR(2),
    IN p_estadoproceso      VARCHAR(2),
    IN p_estadodocumento    VARCHAR(2),
    IN p_tramacdr           LONGTEXT,
    IN p_usuariomodificador VARCHAR(30)
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;

    SELECT COUNT(*) INTO v_existe
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe la retencion';
    END IF;

    UPDATE retenciones
       SET estadosunat        = COALESCE(p_estadosunat,     estadosunat),
           estadoproceso      = COALESCE(p_estadoproceso,   estadoproceso),
           estadodocumento    = COALESCE(p_estadodocumento, estadodocumento),
           tramacdr           = COALESCE(p_tramacdr,        tramacdr),
           usuariomodificador = p_usuariomodificador,
           fechamodificacion  = NOW()
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    SELECT 'OK' AS resultado;
END$$

-- =====================================================================
-- 9) SP_RETENCION_ANULAR (baja logica)
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_ANULAR (
    IN p_rucempresa         VARCHAR(11),
    IN p_serienumero        VARCHAR(15),
    IN p_motivo             VARCHAR(250),
    IN p_usuariomodificador VARCHAR(30)
)
BEGIN
    DECLARE v_existe   INT DEFAULT 0;
    DECLARE v_anulada  INT DEFAULT 0;

    SELECT COUNT(*),
           SUM(CASE WHEN estadodocumento = '11' THEN 1 ELSE 0 END)
      INTO v_existe, v_anulada
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe la retencion';
    END IF;

    IF v_anulada > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La retencion ya esta anulada';
    END IF;

    UPDATE retenciones
       SET estadodocumento    = '11',
           observacion        = TRIM(CONCAT(IFNULL(observacion,''),
                                            CASE WHEN observacion IS NULL OR observacion='' THEN '' ELSE ' | ' END,
                                            'ANULADA: ', IFNULL(p_motivo,''))),
           usuariomodificador = p_usuariomodificador,
           fechamodificacion  = NOW()
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    SELECT 'OK' AS resultado;
END$$

-- =====================================================================
-- 10) SP_RETENCION_REPORTE_PERIODO
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_REPORTE_PERIODO (
    IN p_rucempresa VARCHAR(11),
    IN p_anio       INT,
    IN p_mes        INT
)
BEGIN
    SELECT
        r.numdocproveedor,
        r.razonsocialproveedor,
        COUNT(*)                                                              AS total_comprobantes,
        SUM(CAST(IFNULL(r.importetotalpagado,'0')   AS DECIMAL(18,2)))        AS total_pagado,
        SUM(CAST(IFNULL(r.importetotalretenido,'0') AS DECIMAL(18,2)))        AS total_retenido,
        SUM(CASE WHEN r.estadosunat     = '05' THEN 1 ELSE 0 END)             AS aceptadas,
        SUM(CASE WHEN r.estadosunat     = '09' THEN 1 ELSE 0 END)             AS rechazadas,
        SUM(CASE WHEN r.estadodocumento = '11' THEN 1 ELSE 0 END)             AS anuladas
      FROM retenciones r
     WHERE r.rucempresa = p_rucempresa
       AND r.anio = p_anio
       AND ( p_mes IS NULL OR r.mes = p_mes )
     GROUP BY r.numdocproveedor, r.razonsocialproveedor
     ORDER BY total_retenido DESC;
END$$

-- =====================================================================
-- 11) SP_RETENCION_TOTALES_DASHBOARD
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_TOTALES_DASHBOARD (
    IN p_rucempresa  VARCHAR(11),
    IN p_fecha_desde VARCHAR(10),
    IN p_fecha_hasta VARCHAR(10)
)
BEGIN
    SELECT
        COUNT(*)                                                              AS total_retenciones,
        COUNT(DISTINCT numdocproveedor)                                       AS proveedores_distintos,
        SUM(CAST(IFNULL(importetotalretenido,'0') AS DECIMAL(18,2)))          AS total_retenido,
        SUM(CAST(IFNULL(importetotalpagado,'0')   AS DECIMAL(18,2)))          AS total_pagado,
        SUM(CASE WHEN estadosunat     = '05' THEN 1 ELSE 0 END)               AS aceptadas,
        SUM(CASE WHEN estadosunat     = '09' THEN 1 ELSE 0 END)               AS rechazadas,
        SUM(CASE WHEN estadosunat IS NULL OR estadosunat = '00' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN estadodocumento = '11' THEN 1 ELSE 0 END)               AS anuladas
      FROM retenciones
     WHERE rucempresa = p_rucempresa
       AND ( p_fecha_desde IS NULL OR p_fecha_desde = '' OR fechaemision >= p_fecha_desde )
       AND ( p_fecha_hasta IS NULL OR p_fecha_hasta = '' OR fechaemision <= p_fecha_hasta );
END$$

-- =====================================================================
-- 12) SP_RETENCION_SIGUIENTE_NUMERO
-- Devuelve el siguiente numero correlativo (8 digitos) para una serie.
-- =====================================================================
CREATE PROCEDURE SP_RETENCION_SIGUIENTE_NUMERO (
    IN p_rucempresa VARCHAR(11),
    IN p_serie      VARCHAR(4)
)
BEGIN
    DECLARE v_max INT DEFAULT 0;

    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(serienumero, '-', -1) AS UNSIGNED)), 0)
      INTO v_max
      FROM retenciones
     WHERE rucempresa = p_rucempresa
       AND serienumero LIKE CONCAT(p_serie, '-%');

    SELECT LPAD(v_max + 1, 8, '0') AS siguiente_numero;
END$$

DELIMITER ;

-- =====================================================================
-- VERIFICACION
-- =====================================================================
-- SELECT ROUTINE_NAME FROM information_schema.ROUTINES
--  WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME LIKE 'SP_RETENCION%';
