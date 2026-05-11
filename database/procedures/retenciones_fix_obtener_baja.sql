-- =====================================================================
-- FIX: SP_RETENCION_OBTENER debe devolver tambien las columnas de BAJA
--      (iddocumento_baja, nro_ticket_baja, nombre_archivo_baja,
--       motivo_baja, fecha_envio_baja, respuesta_baja) para que el modal
--      "Ver detalle" pueda mostrar el ticket cuando la retencion esta
--      anulada (estadodocumento = '11').
--
-- IMPORTANTE: correr DESPUES de retenciones_baja_sunat.sql (que crea las
--             columnas). Si no, este SP fallara con "Unknown column".
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
        -- Datos de la baja (Resumen de Reversion CRE) ---------------------
        iddocumento_baja, nro_ticket_baja, nombre_archivo_baja,
        motivo_baja, fecha_envio_baja,
        -- ------------------------------------------------------------------
        usuariocreador, fechacreacion, usuariomodificador, fechamodificacion,
        anio, mes, dia
      FROM retenciones
     WHERE rucempresa = p_rucempresa AND serienumero = p_serienumero;
END$$

DELIMITER ;
