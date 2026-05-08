-- =====================================================================
-- FIX: SP_RETENCION_OBTENER debe devolver tambien las columnas nuevas
--      (codigohash, codigoqr, pdf417, mensaje_error, respuesta_envio)
--      para que el PDF y el modal "Ver detalle" puedan mostrarlas.
-- =====================================================================

DROP PROCEDURE IF EXISTS SP_RETENCION_OBTENER;

DELIMITER $$

CREATE PROCEDURE SP_RETENCION_OBTENER (
    IN p_rucempresa  VARCHAR(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_serienumero VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)
BEGIN
    SELECT
        id, rucempresa, serienumero, versionubl, versionestructura, fechaemision,
        numdocproveedor, tipodocproveedor, direccionproveedor, razonsocialproveedor,
        regimenretencion, tasaretencion, observacion,
        importetotalretenido, monedaimportetotalretenido,
        importetotalpagado, monedaimportetotalpagado,
        estadosunat, estadoproceso, estadodocumento,
        codigohash, codigoqr, pdf417, mensaje_error, respuesta_envio,
        usuariocreador, fechacreacion, usuariomodificador, fechamodificacion,
        anio, mes, dia
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;
END$$

DELIMITER ;
