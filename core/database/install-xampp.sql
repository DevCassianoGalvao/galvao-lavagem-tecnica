-- Instalacao XAMPP - Galvao Lavagem Tecnica
-- Uso via terminal MySQL, executando dentro da pasta core/database:
-- mysql -u root < install-xampp.sql
--
-- Se usar phpMyAdmin, importe primeiro schema.sql e depois seed-admin.sql.

SOURCE schema.sql;
SOURCE seed-admin.sql;
