-- =====================================================================
-- ALTER: agrega columnas de tracking de replicacion al datamarket SQL Server.
-- Idempotente: usa INFORMATION_SCHEMA para no fallar si las columnas existen.
-- =====================================================================

-- USE guia_electronica;

SET @s := (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'retenciones'
        AND COLUMN_NAME = 'replicado_datamarket') = 0,
    'ALTER TABLE retenciones ADD COLUMN replicado_datamarket TINYINT(1) NOT NULL DEFAULT 0 AFTER fechamodificacion',
    'SELECT 1'
  )
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'retenciones'
        AND COLUMN_NAME = 'fecha_replicacion') = 0,
    'ALTER TABLE retenciones ADD COLUMN fecha_replicacion DATETIME NULL AFTER replicado_datamarket',
    'SELECT 1'
  )
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'retenciones'
        AND COLUMN_NAME = 'error_replicacion') = 0,
    'ALTER TABLE retenciones ADD COLUMN error_replicacion VARCHAR(500) NULL AFTER fecha_replicacion',
    'SELECT 1'
  )
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice para filtrar pendientes de reintento
SET @s := (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'retenciones'
        AND INDEX_NAME = 'idx_RET_replicado') = 0,
    'CREATE INDEX idx_RET_replicado ON retenciones (rucempresa, replicado_datamarket)',
    'SELECT 1'
  )
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verificacion
SHOW COLUMNS FROM retenciones LIKE 'replicado_datamarket';
SHOW COLUMNS FROM retenciones LIKE 'fecha_replicacion';
SHOW COLUMNS FROM retenciones LIKE 'error_replicacion';
