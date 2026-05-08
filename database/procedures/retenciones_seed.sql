-- =====================================================================
-- DATOS DE PRUEBA - RETENCIONES (TRAVEL & ADVENTURE RETAIL S.A.)
-- Genera retenciones con detalles variados invocando los SPs.
-- Idempotente: limpia las TEST previas antes de insertar.
-- =====================================================================

-- USE guia_electronica;

SET @ruc  = '20545371821';
SET @hoy  = DATE_FORMAT(NOW(), '%Y-%m-%d');
SET @ayer = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d');
SET @hace7= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d');
SET @user = 'SEED';

-- Limpieza previa (solo de pruebas)
DELETE FROM detalle_retenciones WHERE rucempresa = @ruc AND serienumero LIKE 'R001-TEST%';
DELETE FROM retenciones         WHERE rucempresa = @ruc AND serienumero LIKE 'R001-TEST%';

-- =====================================================================
-- 1) Aceptada por SUNAT, 1 detalle, en SOLES
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0001', '2.0', '1.0', @hoy,
    '20512345671', '06', 'AV. INDUSTRIAL 100, LIMA', 'PROVEEDOR PRUEBA UNO S.A.C.',
    '01', '3.00', 'Retencion de prueba - Aceptada',
    '300.00', 'PEN', '10000.00', 'PEN',
    @user
);
UPDATE retenciones SET estadosunat='05', estadoproceso='05', estadodocumento='05'
 WHERE rucempresa=@ruc AND serienumero='R001-TEST0001';

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0001', '01', 'F001-00000123', @ayer,
    '10000.00', 'PEN', @hoy, '001', '9700.00', 'PEN',
    '300.00', 'PEN', @hoy, '9700.00', 'PEN', @user
);

-- =====================================================================
-- 2) Aceptada con 3 detalles (multiples pagos)
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0002', '2.0', '1.0', @hoy,
    '20512345672', '06', 'CALLE LOS ALAMOS 250, AREQUIPA', 'DISTRIBUIDORA NORTE E.I.R.L.',
    '01', '3.00', 'Retencion con varios documentos',
    '750.00', 'PEN', '25000.00', 'PEN',
    @user
);
UPDATE retenciones SET estadosunat='05', estadoproceso='05', estadodocumento='05'
 WHERE rucempresa=@ruc AND serienumero='R001-TEST0002';

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0002', '01', 'F002-00000045', @ayer,
    '10000.00', 'PEN', @hoy, '001', '9700.00', 'PEN',
    '300.00', 'PEN', @hoy, '9700.00', 'PEN', @user
);
CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0002', '01', 'F002-00000046', @ayer,
    '8000.00', 'PEN', @hoy, '002', '7760.00', 'PEN',
    '240.00', 'PEN', @hoy, '7760.00', 'PEN', @user
);
CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0002', '01', 'F002-00000047', @ayer,
    '7000.00', 'PEN', @hoy, '003', '6790.00', 'PEN',
    '210.00', 'PEN', @hoy, '6790.00', 'PEN', @user
);

-- =====================================================================
-- 3) Rechazada por SUNAT
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0003', '2.0', '1.0', @hace7,
    '20512345673', '06', 'JR. PUNO 500, CUSCO', 'SERVICIOS GENERALES DEL SUR S.A.',
    '01', '3.00', 'Retencion rechazada - error en datos',
    '450.00', 'PEN', '15000.00', 'PEN',
    @user
);
UPDATE retenciones SET estadosunat='09', estadoproceso='09', estadodocumento='02'
 WHERE rucempresa=@ruc AND serienumero='R001-TEST0003';

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0003', '01', 'F003-00000010', @hace7,
    '15000.00', 'PEN', @hace7, '001', '14550.00', 'PEN',
    '450.00', 'PEN', @hace7, '14550.00', 'PEN', @user
);

-- =====================================================================
-- 4) Pendiente (sin enviar a SUNAT)
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0004', '2.0', '1.0', @hoy,
    '20512345674', '06', 'AV. SAN MARTIN 75, TRUJILLO', 'IMPORTACIONES PACIFICO S.R.L.',
    '01', '3.00', 'Retencion pendiente de envio',
    '120.00', 'PEN', '4000.00', 'PEN',
    @user
);

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0004', '01', 'F004-00000088', @hoy,
    '4000.00', 'PEN', @hoy, '001', '3880.00', 'PEN',
    '120.00', 'PEN', @hoy, '3880.00', 'PEN', @user
);

-- =====================================================================
-- 5) Aceptada en DOLARES
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0005', '2.0', '1.0', @ayer,
    '20512345675', '06', 'AV. JAVIER PRADO 1500, LIMA', 'TECH IMPORT CORP S.A.',
    '01', '3.00', 'Retencion en dolares',
    '180.00', 'USD', '6000.00', 'USD',
    @user
);
UPDATE retenciones SET estadosunat='05', estadoproceso='05', estadodocumento='05'
 WHERE rucempresa=@ruc AND serienumero='R001-TEST0005';

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0005', '01', 'F005-00000033', @ayer,
    '6000.00', 'USD', @hoy, '001', '5820.00', 'USD',
    '180.00', 'USD', @hoy, '5820.00', 'USD', @user
);

-- =====================================================================
-- 6) Aceptada con monto alto (proveedor frecuente, 2 detalles)
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0006', '2.0', '1.0', @ayer,
    '20512345676', '06', 'AV. ARGENTINA 3000, CALLAO', 'LOGISTICA INTEGRAL DEL PERU S.A.C.',
    '01', '3.00', 'Retencion proveedor frecuente',
    '1500.00', 'PEN', '50000.00', 'PEN',
    @user
);
UPDATE retenciones SET estadosunat='05', estadoproceso='05', estadodocumento='05'
 WHERE rucempresa=@ruc AND serienumero='R001-TEST0006';

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0006', '01', 'F006-00000200', @ayer,
    '30000.00', 'PEN', @hoy, '001', '29100.00', 'PEN',
    '900.00', 'PEN', @hoy, '29100.00', 'PEN', @user
);
CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0006', '01', 'F006-00000201', @ayer,
    '20000.00', 'PEN', @hoy, '002', '19400.00', 'PEN',
    '600.00', 'PEN', @hoy, '19400.00', 'PEN', @user
);

-- =====================================================================
-- 7) ANULADA (probamos SP_RETENCION_ANULAR)
-- =====================================================================
CALL SP_RETENCION_INSERT(
    @ruc, 'R001-TEST0007', '2.0', '1.0', @hoy,
    '20512345677', '06', 'AV. UNIVERSITARIA 450, LIMA', 'COMERCIAL LA UNION S.A.',
    '01', '3.00', 'Retencion para anular',
    '60.00', 'PEN', '2000.00', 'PEN',
    @user
);

CALL SP_DETALLERETENCION_INSERT(
    @ruc, 'R001-TEST0007', '01', 'F007-00000005', @hoy,
    '2000.00', 'PEN', @hoy, '001', '1940.00', 'PEN',
    '60.00', 'PEN', @hoy, '1940.00', 'PEN', @user
);

CALL SP_RETENCION_ANULAR(@ruc, 'R001-TEST0007', 'Anulada por error en monto', @user);

-- =====================================================================
-- VERIFICACION
-- =====================================================================
SELECT
    serienumero,
    razonsocialproveedor,
    importetotalretenido,
    monedaimportetotalretenido AS mon,
    estadosunat,
    estadodocumento
  FROM retenciones
 WHERE rucempresa = @ruc AND serienumero LIKE 'R001-TEST%'
 ORDER BY serienumero;

SELECT
    serienumero,
    COUNT(*) AS detalles,
    SUM(CAST(importeretenido AS DECIMAL(18,2))) AS suma_retenido
  FROM detalle_retenciones
 WHERE rucempresa = @ruc AND serienumero LIKE 'R001-TEST%'
 GROUP BY serienumero
 ORDER BY serienumero;
