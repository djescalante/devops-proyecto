-- 000_schema.sql
-- Crea la tabla con la columna 'celular' para instalaciones nuevas
CREATE TABLE IF NOT EXISTS mensajes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(180) NOT NULL,
  celular VARCHAR(20),
  mensaje TEXT NOT NULL,
  fecha DATETIME NOT NULL
);
