-- Usuario inicial para teste local.
-- Login: admin@galvao.local
-- Senha: Admin@12345

USE galvao_lavagem_tecnica;

INSERT INTO users (
  name,
  email,
  phone,
  password_hash,
  role,
  status,
  created_at,
  updated_at
) VALUES (
  'Administrador Galvao',
  'admin@galvao.local',
  '',
  '$2y$10$uFL0uIC23CWfvKFlHnSTbOYKbAlTAjTI.J1M5DWnpgNZTZUQQwOG2',
  'owner',
  'active',
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  status = VALUES(status),
  updated_at = NOW();
