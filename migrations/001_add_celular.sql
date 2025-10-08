-- 001_add_celular.sql
-- Agrega la columna 'celular' si no existe (MySQL 8+)
ALTER TABLE mensajes
  ADD COLUMN IF NOT EXISTS celular VARCHAR(20) AFTER correo;
