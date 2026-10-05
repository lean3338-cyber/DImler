USE teamdimler;

SET @tiene_rol = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'rol'
);

SET @agregar_rol = IF(
    @tiene_rol = 0,
    'ALTER TABLE usuarios ADD COLUMN rol ENUM(''cliente'', ''admin'') NOT NULL DEFAULT ''cliente'' AFTER password_hash',
    'SELECT ''La columna rol ya existe'' AS resultado'
);

PREPARE migracion_rol FROM @agregar_rol;
EXECUTE migracion_rol;
DEALLOCATE PREPARE migracion_rol;

-- Cambiá el correo por el de tu cuenta y ejecutá esta consulta para habilitarla como admin.
UPDATE usuarios
SET rol = 'admin'
WHERE email = 'TU_CORREO@EJEMPLO.COM';

SELECT id, nombre, email, rol
FROM usuarios
WHERE email = 'TU_CORREO@EJEMPLO.COM';
