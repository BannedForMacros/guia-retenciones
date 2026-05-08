-- =====================================================================
-- FIX: SP_RETENCION_INSERT y SP_DETALLERETENCION_INSERT no deben
--   - manejar su propia transaccion (rompen la transaccion de Laravel)
--   - devolver resultset trailing (deja conexion en estado "unbuffered queries active")
--
-- La transaccion la maneja Laravel con DB::beginTransaction()/commit()/rollBack().
-- =====================================================================

-- USE guia_electronica;

DROP PROCEDURE IF EXISTS SP_RETENCION_INSERT;
DROP PROCEDURE IF EXISTS SP_DETALLERETENCION_INSERT;

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
        NULL, 'P', NULL,
        p_usuariocreador, NOW(),
        CAST(SUBSTRING(p_fechaemision,1,4) AS UNSIGNED),
        CAST(SUBSTRING(p_fechaemision,6,2) AS UNSIGNED),
        CAST(SUBSTRING(p_fechaemision,9,2) AS UNSIGNED)
    );
END$$

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

    SELECT COUNT(*) INTO v_cab FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;

    IF v_cab = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe la cabecera de retencion (rucempresa + serienumero)';
    END IF;

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
END$$

DELIMITER ;
