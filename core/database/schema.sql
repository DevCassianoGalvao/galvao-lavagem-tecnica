-- Galvao Lavagem Tecnica
-- Modelagem relacional MySQL para CRM, quiz tecnico, kanban, calendario,
-- uploads centralizados, IA textual, IA visual e auditoria.
--
-- Principios:
-- 1. Imagens nao sao duplicadas: a tabela uploads guarda o arquivo uma vez.
-- 2. Qualquer entidade usa upload_links para referenciar uploads por ID.
-- 3. Dados repetitivos sao normalizados em tabelas relacionais.
-- 4. Todas as consultas da aplicacao devem usar PDO prepared statements.

CREATE DATABASE IF NOT EXISTS galvao_lavagem_tecnica
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE galvao_lavagem_tecnica;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS login_history;
DROP TABLE IF EXISTS queue_job_logs;
DROP TABLE IF EXISTS queue_jobs;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS internal_notifications;
DROP TABLE IF EXISTS recurrence_plans;
DROP TABLE IF EXISTS ai_jobs;
DROP TABLE IF EXISTS ai_image_usages;
DROP TABLE IF EXISTS ai_images;
DROP TABLE IF EXISTS ai_classifications;
DROP TABLE IF EXISTS ai_tag_suggestions;
DROP TABLE IF EXISTS ai_summaries;
DROP TABLE IF EXISTS product_usages;
DROP TABLE IF EXISTS product_surface_compatibilities;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS client_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS calendar_events;
DROP TABLE IF EXISTS service_surfaces;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS upload_links;
DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS lead_surface_interests;
DROP TABLE IF EXISTS lead_dirt_types;
DROP TABLE IF EXISTS follow_ups;
DROP TABLE IF EXISTS lead_stage_history;
DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS surfaces;
DROP TABLE IF EXISTS surface_types;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS property_types;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS pipeline_stages;
DROP TABLE IF EXISTS dirt_types;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL COMMENT 'Nome completo do usuario interno.',
  email VARCHAR(160) NOT NULL COMMENT 'E-mail unico para login.',
  phone VARCHAR(40) NULL,
  password_hash VARCHAR(255) NOT NULL COMMENT 'Hash seguro da senha. Nunca salvar senha pura.',
  role ENUM('owner', 'admin', 'manager', 'operator', 'commercial', 'viewer') NOT NULL DEFAULT 'operator',
  status ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB COMMENT='Usuarios internos preparados para operacao multiusuario.';

CREATE TABLE login_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  email VARCHAR(160) NULL,
  status ENUM('success', 'failed', 'logout', 'timeout', 'remembered') NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  failure_reason VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_history_user_created (user_id, created_at),
  KEY idx_login_history_email_created (email, created_at),
  KEY idx_login_history_status_created (status, created_at),
  CONSTRAINT fk_login_history_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Historico de autenticacao, falhas e encerramentos de sessao.';

CREATE TABLE remember_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  last_used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_remember_tokens_selector (selector),
  KEY idx_remember_tokens_user_active (user_id, revoked_at, expires_at),
  CONSTRAINT fk_remember_tokens_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Tokens persistentes para remember me com seletor publico e token hasheado.';

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_resets_selector (selector),
  KEY idx_password_resets_user_created (user_id, created_at),
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Tokens de recuperacao de senha de uso unico.';

CREATE TABLE queue_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('ai_text', 'ai_visual', 'thumbnail', 'compression', 'notification') NOT NULL COMMENT 'Canal da fila: IA textual, IA visual, imagem ou notificacoes.',
  status ENUM('pending', 'processing', 'completed', 'failed', 'canceled') NOT NULL DEFAULT 'pending',
  priority TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Prioridade de 1 a 10. Valores maiores saem primeiro.',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
  payload_json JSON NULL COMMENT 'Entrada do job. Guardar apenas referencias por ID, nunca duplicar imagens.',
  result_json JSON NULL COMMENT 'Resultado resumido do processamento.',
  available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reserved_at DATETIME NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_queue_jobs_status_available (status, available_at),
  KEY idx_queue_jobs_type_status (type, status),
  KEY idx_queue_jobs_priority_available (priority, available_at),
  KEY idx_queue_jobs_created (created_at)
) ENGINE=InnoDB COMMENT='Fila generica para tarefas pesadas e processamento assincrono.';

CREATE TABLE queue_job_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  level ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
  event VARCHAR(80) NOT NULL,
  message TEXT NOT NULL,
  context_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_queue_job_logs_job_created (job_id, created_at),
  KEY idx_queue_job_logs_level_created (level, created_at),
  CONSTRAINT fk_queue_job_logs_job
    FOREIGN KEY (job_id) REFERENCES queue_jobs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Logs de execucao, retry e erro dos jobs da fila.';

CREATE TABLE pipeline_stages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#C8A95B',
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_won TINYINT(1) NOT NULL DEFAULT 0,
  is_lost TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pipeline_stages_slug (slug),
  KEY idx_pipeline_stages_position (position)
) ENGINE=InnoDB COMMENT='Colunas do kanban comercial.';

CREATE TABLE property_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_property_types_slug (slug)
) ENGINE=InnoDB COMMENT='Tipos de imovel: residencia, condominio, empresa, comercio.';

CREATE TABLE surface_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(90) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_surface_types_slug (slug)
) ENGINE=InnoDB COMMENT='Catalogo normalizado de superficies avaliadas.';

CREATE TABLE dirt_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(90) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dirt_types_slug (slug)
) ENGINE=InnoDB COMMENT='Catalogo de tipos de sujeira: lodo, musgo, mofo, ferrugem etc.';

CREATE TABLE clients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_user_id BIGINT UNSIGNED NULL COMMENT 'Usuario responsavel pela conta do cliente.',
  name VARCHAR(140) NOT NULL,
  document_number VARCHAR(30) NULL COMMENT 'CPF/CNPJ opcional.',
  email VARCHAR(160) NULL,
  phone VARCHAR(40) NOT NULL,
  whatsapp VARCHAR(40) NULL,
  preferred_contact ENUM('phone', 'whatsapp', 'email') NOT NULL DEFAULT 'whatsapp',
  address_line VARCHAR(180) NULL,
  address_number VARCHAR(30) NULL,
  address_complement VARCHAR(80) NULL,
  neighborhood VARCHAR(100) NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(2) NULL,
  postal_code VARCHAR(20) NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  source VARCHAR(80) NULL,
  status ENUM('active', 'inactive', 'prospect') NOT NULL DEFAULT 'prospect',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_clients_name (name),
  KEY idx_clients_phone (phone),
  KEY idx_clients_email (email),
  KEY idx_clients_city_state (city, state),
  KEY idx_clients_owner_status (owner_user_id, status),
  CONSTRAINT fk_clients_owner_user FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_clients_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_clients_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Clientes com historico, localizacao, tags, imagens e timeline via tabelas relacionadas.';

CREATE TABLE properties (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  property_type_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL COMMENT 'Ex: Casa principal, Condominio Jardins, Loja Centro.',
  address_line VARCHAR(180) NOT NULL,
  address_number VARCHAR(30) NULL,
  address_complement VARCHAR(80) NULL,
  neighborhood VARCHAR(100) NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(2) NULL,
  postal_code VARCHAR(20) NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  access_notes TEXT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_properties_client (client_id),
  KEY idx_properties_type (property_type_id),
  KEY idx_properties_city_state (city, state),
  CONSTRAINT fk_properties_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_properties_type FOREIGN KEY (property_type_id) REFERENCES property_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_properties_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_properties_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Imoveis vinculados ao cliente, com localizacao precisa.';

CREATE TABLE surfaces (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  surface_type_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL COMMENT 'Ex: Garagem frontal, muro lateral, area gourmet.',
  approximate_area_m2 DECIMAL(10, 2) NULL,
  access_difficulty ENUM('easy', 'medium', 'hard') NULL,
  has_elevated_height TINYINT(1) NOT NULL DEFAULT 0,
  current_condition ENUM('unknown', 'light', 'moderate', 'severe') NOT NULL DEFAULT 'unknown',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_surfaces_property (property_id),
  KEY idx_surfaces_type (surface_type_id),
  KEY idx_surfaces_condition (current_condition),
  CONSTRAINT fk_surfaces_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_surfaces_type FOREIGN KEY (surface_type_id) REFERENCES surface_types(id) ON DELETE RESTRICT,
  CONSTRAINT fk_surfaces_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_surfaces_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Superficies reais do imovel. Servicos, imagens e IA podem referenciar esta entidade.';

CREATE TABLE leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NULL COMMENT 'Preenchido quando o lead vira cliente ou e associado a um cliente existente.',
  property_id BIGINT UNSIGNED NULL,
  pipeline_stage_id BIGINT UNSIGNED NULL,
  assigned_user_id BIGINT UNSIGNED NULL,
  name VARCHAR(140) NOT NULL,
  email VARCHAR(160) NULL,
  phone VARCHAR(40) NOT NULL,
  source VARCHAR(80) NULL COMMENT 'Landing, quiz, indicacao, organico, trafego pago etc.',
  priority ENUM('aesthetic', 'safety', 'valuation', 'maintenance') NULL,
  area_size ENUM('small', 'medium', 'large') NULL,
  approximate_area_m2 DECIMAL(10, 2) NULL,
  access_difficulty ENUM('easy', 'medium', 'hard') NULL,
  has_elevated_height TINYINT(1) NOT NULL DEFAULT 0,
  cleaning_frequency ENUM('never', 'rarely', 'frequently') NULL,
  score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('new', 'qualified', 'proposal', 'scheduled', 'won', 'lost', 'archived') NOT NULL DEFAULT 'new',
  lost_reason VARCHAR(180) NULL,
  next_follow_up_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_leads_stage (pipeline_stage_id),
  KEY idx_leads_status_created (status, created_at),
  KEY idx_leads_assigned_followup (assigned_user_id, next_follow_up_at),
  KEY idx_leads_client (client_id),
  KEY idx_leads_property (property_id),
  KEY idx_leads_phone (phone),
  KEY idx_leads_email (email),
  CONSTRAINT fk_leads_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_pipeline_stage FOREIGN KEY (pipeline_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Leads vindos da landing, quiz, campanhas ou cadastro manual.';

CREATE TABLE lead_surface_interests (
  lead_id BIGINT UNSIGNED NOT NULL,
  surface_type_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (lead_id, surface_type_id),
  CONSTRAINT fk_lsi_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_lsi_surface_type FOREIGN KEY (surface_type_id) REFERENCES surface_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Superficies selecionadas no quiz ou interesse do lead sem duplicar texto.';

CREATE TABLE lead_dirt_types (
  lead_id BIGINT UNSIGNED NOT NULL,
  dirt_type_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (lead_id, dirt_type_id),
  CONSTRAINT fk_ldt_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ldt_dirt_type FOREIGN KEY (dirt_type_id) REFERENCES dirt_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Tipos de sujeira informados no quiz ou avaliacao comercial.';

CREATE TABLE lead_stage_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NOT NULL,
  from_stage_id BIGINT UNSIGNED NULL,
  to_stage_id BIGINT UNSIGNED NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  note VARCHAR(255) NULL,
  KEY idx_lsh_lead_changed (lead_id, changed_at),
  CONSTRAINT fk_lsh_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_lsh_from_stage FOREIGN KEY (from_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL,
  CONSTRAINT fk_lsh_to_stage FOREIGN KEY (to_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL,
  CONSTRAINT fk_lsh_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Historico de movimentacao do kanban.';

CREATE TABLE follow_ups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NULL,
  client_id BIGINT UNSIGNED NULL,
  service_id BIGINT UNSIGNED NULL,
  assigned_user_id BIGINT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  due_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  status ENUM('pending', 'done', 'canceled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_followups_due_status (due_at, status),
  KEY idx_followups_assigned_due (assigned_user_id, due_at),
  KEY idx_followups_service (service_id),
  CONSTRAINT fk_followups_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_followups_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_followups_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Follow-ups comerciais e operacionais.';

CREATE TABLE uploads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uploaded_by BIGINT UNSIGNED NULL,
  original_name VARCHAR(180) NOT NULL,
  stored_name VARCHAR(180) NOT NULL,
  storage_disk VARCHAR(40) NOT NULL DEFAULT 'local',
  storage_path VARCHAR(255) NOT NULL COMMENT 'Caminho fisico relativo. Nao duplicar este registro para a mesma imagem.',
  public_url VARCHAR(255) NULL,
  mime_type VARCHAR(100) NOT NULL,
  extension VARCHAR(12) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  width_px INT UNSIGNED NULL,
  height_px INT UNSIGNED NULL,
  sha256_hash CHAR(64) NOT NULL COMMENT 'Hash unico para evitar duplicidade de arquivo.',
  image_role ENUM('original', 'thumbnail', 'ai_generated', 'document', 'other') NOT NULL DEFAULT 'original',
  status ENUM('temporary', 'active', 'archived', 'deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_uploads_sha256 (sha256_hash),
  UNIQUE KEY uq_uploads_storage_path (storage_path),
  KEY idx_uploads_uploaded_by (uploaded_by),
  KEY idx_uploads_status_created (status, created_at),
  KEY idx_uploads_role_status_created (image_role, status, created_at),
  KEY idx_uploads_mime_status_created (mime_type, status, created_at),
  CONSTRAINT fk_uploads_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Banco visual central. Uma imagem existe uma vez e e referenciada por IDs.';

CREATE TABLE upload_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  upload_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  property_id BIGINT UNSIGNED NULL,
  surface_id BIGINT UNSIGNED NULL,
  service_id BIGINT UNSIGNED NULL,
  calendar_event_id BIGINT UNSIGNED NULL,
  note_id BIGINT UNSIGNED NULL,
  relation_type ENUM('diagnostic', 'before', 'after', 'ai_source', 'ai_result', 'attachment', 'document') NOT NULL DEFAULT 'attachment',
  caption VARCHAR(180) NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_upload_links_upload (upload_id),
  KEY idx_upload_links_client (client_id),
  KEY idx_upload_links_lead (lead_id),
  KEY idx_upload_links_property (property_id),
  KEY idx_upload_links_surface (surface_id),
  KEY idx_upload_links_service (service_id),
  KEY idx_upload_links_relation_created (relation_type, created_at),
  KEY idx_upload_links_visual_filters (client_id, property_id, surface_id, service_id),
  CONSTRAINT fk_ul_upload FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_surface FOREIGN KEY (surface_id) REFERENCES surfaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Vinculos de uploads. Permite reutilizar a mesma imagem em cliente, lead, superficie e servico.';

CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  lead_id BIGINT UNSIGNED NULL,
  assigned_user_id BIGINT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  service_type VARCHAR(100) NOT NULL COMMENT 'Ex: revitalizacao externa, lavagem tecnica, manutencao preventiva.',
  status ENUM('draft', 'quoted', 'approved', 'scheduled', 'in_progress', 'completed', 'canceled') NOT NULL DEFAULT 'draft',
  priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  quoted_amount DECIMAL(12, 2) NULL,
  final_amount DECIMAL(12, 2) NULL,
  scheduled_start_at DATETIME NULL,
  scheduled_end_at DATETIME NULL,
  completed_at DATETIME NULL,
  technical_report TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_services_client_status (client_id, status),
  KEY idx_services_property (property_id),
  KEY idx_services_schedule (scheduled_start_at, scheduled_end_at),
  KEY idx_services_assigned_status (assigned_user_id, status),
  CONSTRAINT fk_services_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
  CONSTRAINT fk_services_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE RESTRICT,
  CONSTRAINT fk_services_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  CONSTRAINT fk_services_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_services_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_services_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Ordens/propostas de servico tecnico.';

ALTER TABLE upload_links
  ADD CONSTRAINT fk_ul_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE;

ALTER TABLE follow_ups
  ADD CONSTRAINT fk_followups_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE;

CREATE TABLE service_surfaces (
  service_id BIGINT UNSIGNED NOT NULL,
  surface_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (service_id, surface_id),
  CONSTRAINT fk_ss_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_ss_surface FOREIGN KEY (surface_id) REFERENCES surfaces(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Superficies executadas em cada servico.';

CREATE TABLE calendar_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  client_id BIGINT UNSIGNED NULL,
  assigned_user_id BIGINT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  event_type ENUM('visit', 'service', 'follow_up', 'internal', 'delivery') NOT NULL DEFAULT 'service',
  status ENUM('pending', 'confirmed', 'done', 'canceled', 'rescheduled') NOT NULL DEFAULT 'pending',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  location_text VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_calendar_start_status (starts_at, status),
  KEY idx_calendar_assigned_start (assigned_user_id, starts_at),
  KEY idx_calendar_service (service_id),
  CONSTRAINT fk_calendar_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
  CONSTRAINT fk_calendar_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  CONSTRAINT fk_calendar_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  CONSTRAINT fk_calendar_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_calendar_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_calendar_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Calendario tecnico, comercial e operacional.';

ALTER TABLE upload_links
  ADD CONSTRAINT fk_ul_calendar_event FOREIGN KEY (calendar_event_id) REFERENCES calendar_events(id) ON DELETE CASCADE;

CREATE TABLE recurrence_plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  source_service_id BIGINT UNSIGNED NOT NULL COMMENT 'Servico concluido que originou a recorrencia.',
  follow_up_id BIGINT UNSIGNED NULL,
  calendar_event_id BIGINT UNSIGNED NULL,
  interval_months SMALLINT UNSIGNED NOT NULL DEFAULT 6,
  next_due_at DATETIME NOT NULL,
  last_service_at DATETIME NULL,
  status ENUM('active', 'paused', 'completed', 'canceled') NOT NULL DEFAULT 'active',
  reason VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_recurrence_source_service (source_service_id),
  KEY idx_recurrence_next_status (next_due_at, status),
  KEY idx_recurrence_client_status (client_id, status),
  CONSTRAINT fk_recurrence_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_recurrence_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
  CONSTRAINT fk_recurrence_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  CONSTRAINT fk_recurrence_service FOREIGN KEY (source_service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_recurrence_followup FOREIGN KEY (follow_up_id) REFERENCES follow_ups(id) ON DELETE SET NULL,
  CONSTRAINT fk_recurrence_calendar FOREIGN KEY (calendar_event_id) REFERENCES calendar_events(id) ON DELETE SET NULL,
  CONSTRAINT fk_recurrence_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_recurrence_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Planos inteligentes de retorno preventivo apos revitalizacoes.';

CREATE TABLE internal_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  service_id BIGINT UNSIGNED NULL,
  calendar_event_id BIGINT UNSIGNED NULL,
  follow_up_id BIGINT UNSIGNED NULL,
  recurrence_plan_id BIGINT UNSIGNED NULL,
  notification_type ENUM('new_lead', 'follow_up', 'preventive_return', 'scheduled_event', 'stalled_lead', 'proposal', 'system') NOT NULL DEFAULT 'system',
  priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  title VARCHAR(160) NOT NULL,
  body TEXT NOT NULL,
  action_url VARCHAR(255) NULL,
  channel ENUM('in_app', 'email', 'whatsapp', 'push') NOT NULL DEFAULT 'in_app',
  status ENUM('pending', 'sent', 'read', 'dismissed', 'failed') NOT NULL DEFAULT 'pending',
  notify_at DATETIME NOT NULL,
  sent_at DATETIME NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_notifications_user_status_notify (user_id, status, notify_at),
  KEY idx_notifications_type_priority (notification_type, priority),
  KEY idx_notifications_client (client_id),
  KEY idx_notifications_calendar (calendar_event_id),
  KEY idx_notifications_recurrence (recurrence_plan_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_notifications_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_calendar FOREIGN KEY (calendar_event_id) REFERENCES calendar_events(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_followup FOREIGN KEY (follow_up_id) REFERENCES follow_ups(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_recurrence FOREIGN KEY (recurrence_plan_id) REFERENCES recurrence_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Notificacoes internas com estrutura pronta para WhatsApp e automacoes futuras.';

CREATE TABLE tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#C8A95B',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tags_slug (slug)
) ENGINE=InnoDB COMMENT='Tags reutilizaveis para segmentacao de clientes.';

CREATE TABLE client_tags (
  client_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (client_id, tag_id),
  CONSTRAINT fk_client_tags_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_tags_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Relacionamento N:N entre clientes e tags.';

CREATE TABLE notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  property_id BIGINT UNSIGNED NULL,
  service_id BIGINT UNSIGNED NULL,
  surface_id BIGINT UNSIGNED NULL,
  author_user_id BIGINT UNSIGNED NULL,
  note_type ENUM('general', 'attendance', 'operational', 'financial', 'technical', 'commercial', 'timeline', 'system') NOT NULL DEFAULT 'general',
  title VARCHAR(160) NULL,
  body TEXT NOT NULL,
  tags_json JSON NULL,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_notes_client_created (client_id, created_at),
  KEY idx_notes_lead_created (lead_id, created_at),
  KEY idx_notes_service_created (service_id, created_at),
  KEY idx_notes_type_pinned_created (note_type, is_pinned, created_at),
  FULLTEXT KEY ft_notes_body (title, body),
  CONSTRAINT fk_notes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_surface FOREIGN KEY (surface_id) REFERENCES surfaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Observacoes e timeline tecnica/comercial.';

ALTER TABLE upload_links
  ADD CONSTRAINT fk_ul_note FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE;

CREATE TABLE products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  sku VARCHAR(80) NULL,
  category VARCHAR(100) NULL,
  description TEXT NULL,
  dilution VARCHAR(80) NULL COMMENT 'Ex: 1:10, pronto uso, 2% em pulverizacao.',
  application_notes TEXT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'un',
  safety_notes TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_name (name),
  KEY idx_products_active (is_active)
) ENGINE=InnoDB COMMENT='Produtos e insumos utilizados em servicos.';

CREATE TABLE product_surface_compatibilities (
  product_id BIGINT UNSIGNED NOT NULL,
  surface_type_id BIGINT UNSIGNED NOT NULL,
  recommendation ENUM('allowed', 'caution', 'not_recommended') NOT NULL DEFAULT 'allowed',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id, surface_type_id),
  CONSTRAINT fk_psc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_psc_surface_type FOREIGN KEY (surface_type_id) REFERENCES surface_types(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Compatibilidade tecnica entre produtos e superficies.';

CREATE TABLE product_usages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  surface_id BIGINT UNSIGNED NULL,
  quantity DECIMAL(10, 3) NOT NULL DEFAULT 0,
  unit VARCHAR(30) NOT NULL DEFAULT 'un',
  dilution_used VARCHAR(80) NULL,
  result_summary VARCHAR(255) NULL,
  notes VARCHAR(255) NULL,
  used_by BIGINT UNSIGNED NULL,
  used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_product_usages_service (service_id),
  KEY idx_product_usages_client (client_id),
  KEY idx_product_usages_product (product_id),
  KEY idx_product_usages_surface (surface_id),
  KEY idx_product_usages_used_at (used_at),
  CONSTRAINT fk_product_usages_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  CONSTRAINT fk_product_usages_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_product_usages_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_product_usages_surface FOREIGN KEY (surface_id) REFERENCES surfaces(id) ON DELETE SET NULL,
  CONSTRAINT fk_product_usages_used_by FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Historico de produtos aplicados por servico/superficie.';

CREATE TABLE ai_summaries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  property_id BIGINT UNSIGNED NULL,
  service_id BIGINT UNSIGNED NULL,
  generated_by_user_id BIGINT UNSIGNED NULL,
  model_name VARCHAR(120) NULL,
  summary_type ENUM('lead', 'client', 'technical', 'follow_up', 'proposal') NOT NULL DEFAULT 'technical',
  prompt_hash CHAR(64) NULL,
  summary_text MEDIUMTEXT NOT NULL,
  confidence DECIMAL(5, 4) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ai_summaries_client_created (client_id, created_at),
  KEY idx_ai_summaries_lead_created (lead_id, created_at),
  KEY idx_ai_summaries_service_created (service_id, created_at),
  CONSTRAINT fk_ai_summaries_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_summaries_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_summaries_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_summaries_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_summaries_user FOREIGN KEY (generated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='IA textual: resumos, diagnosticos, follow-ups e propostas.';

CREATE TABLE ai_tag_suggestions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  tag_id BIGINT UNSIGNED NULL COMMENT 'Preenchido quando a sugestao for aprovada e vinculada a uma tag real.',
  tag_name VARCHAR(80) NOT NULL,
  confidence DECIMAL(5, 4) NULL,
  model_name VARCHAR(120) NULL,
  prompt_hash CHAR(64) NOT NULL,
  status ENUM('suggested', 'approved', 'rejected', 'applied') NOT NULL DEFAULT 'suggested',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ai_tag_suggestions_lead_hash (lead_id, prompt_hash),
  KEY idx_ai_tag_suggestions_client (client_id),
  KEY idx_ai_tag_suggestions_status (status),
  CONSTRAINT fk_ai_tag_suggestions_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_tag_suggestions_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_tag_suggestions_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Tags automaticas sugeridas por IA, sem aplicar ao cliente antes de aprovacao.';

CREATE TABLE ai_classifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  classification ENUM('simples', 'medio', 'pesado') NOT NULL,
  reason VARCHAR(255) NOT NULL,
  confidence DECIMAL(5, 4) NULL,
  model_name VARCHAR(120) NULL,
  prompt_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ai_classifications_lead_hash (lead_id, prompt_hash),
  KEY idx_ai_classifications_classification (classification),
  CONSTRAINT fk_ai_classifications_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_classifications_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Classificacao operacional gerada por IA textual.';

CREATE TABLE ai_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_upload_id BIGINT UNSIGNED NOT NULL COMMENT 'Imagem original enviada pelo cliente ou equipe.',
  result_upload_id BIGINT UNSIGNED NULL COMMENT 'Imagem gerada/simulada. Tambem aponta para uploads, sem duplicar arquivo.',
  client_id BIGINT UNSIGNED NULL,
  lead_id BIGINT UNSIGNED NULL,
  property_id BIGINT UNSIGNED NULL,
  surface_id BIGINT UNSIGNED NULL,
  service_id BIGINT UNSIGNED NULL,
  model_name VARCHAR(120) NULL,
  prompt_text TEXT NULL,
  status ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
  analysis_json JSON NULL COMMENT 'Metadados tecnicos retornados pela IA visual.',
  error_message TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ai_images_source (source_upload_id),
  KEY idx_ai_images_result (result_upload_id),
  KEY idx_ai_images_status_created (status, created_at),
  KEY idx_ai_images_client (client_id),
  KEY idx_ai_images_lead (lead_id),
  CONSTRAINT fk_ai_images_source_upload FOREIGN KEY (source_upload_id) REFERENCES uploads(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ai_images_result_upload FOREIGN KEY (result_upload_id) REFERENCES uploads(id) ON DELETE SET NULL,
  CONSTRAINT fk_ai_images_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_images_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_images_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_images_surface FOREIGN KEY (surface_id) REFERENCES surfaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_images_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_images_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='IA visual com referencias para uploads originais e resultados.';

CREATE TABLE ai_image_usages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NOT NULL,
  session_id VARCHAR(128) NOT NULL,
  source_upload_id BIGINT UNSIGNED NOT NULL,
  result_upload_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ai_image_usages_session_created (session_id, created_at),
  KEY idx_ai_image_usages_ip_created (ip_address, created_at),
  KEY idx_ai_image_usages_lead_created (lead_id, created_at),
  CONSTRAINT fk_ai_image_usages_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  CONSTRAINT fk_ai_image_usages_source FOREIGN KEY (source_upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_image_usages_result FOREIGN KEY (result_upload_id) REFERENCES uploads(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Controle invisivel de uso de simulacoes visuais por IP, sessao e lead.';

CREATE TABLE ai_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_type ENUM('text_analysis', 'tag_generation', 'classification', 'visual_analysis') NOT NULL,
  entity_type VARCHAR(60) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  payload_json JSON NULL,
  priority TINYINT UNSIGNED NOT NULL DEFAULT 5,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('queued', 'processing', 'completed', 'failed', 'canceled') NOT NULL DEFAULT 'queued',
  available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ai_jobs_status_available (status, available_at),
  KEY idx_ai_jobs_entity (entity_type, entity_id),
  KEY idx_ai_jobs_priority (priority)
) ENGINE=InnoDB COMMENT='Fila preparada para processamento assincrono de IA.';

CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value TEXT NULL,
  value_type ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
  scope ENUM('global', 'user') NOT NULL DEFAULT 'global',
  user_id BIGINT UNSIGNED NULL,
  effective_user_id BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(user_id, 0)) STORED COMMENT 'Garante unicidade tambem para settings globais com user_id NULL.',
  is_private TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Nao exibir em telas publicas quando true.',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_settings_scope_key_user (scope, setting_key, effective_user_id),
  KEY idx_settings_key (setting_key),
  CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Configuracoes globais e preferencias por usuario.';

CREATE TABLE rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_key CHAR(64) NOT NULL,
  action VARCHAR(80) NOT NULL,
  identity_hash CHAR(64) NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  reset_at DATETIME NOT NULL,
  last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rate_limits_key (rate_key),
  KEY idx_rate_limits_action_identity (action, identity_hash),
  KEY idx_rate_limits_reset (reset_at)
) ENGINE=InnoDB COMMENT='Buckets de rate limit por IP, sessao, usuario ou acao sensivel.';

CREATE TABLE logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL DEFAULT 'info',
  channel VARCHAR(80) NOT NULL DEFAULT 'app',
  action VARCHAR(120) NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  message TEXT NOT NULL,
  context_json JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_logs_level_created (level, created_at),
  KEY idx_logs_channel_created (channel, created_at),
  KEY idx_logs_action_created (action, created_at),
  KEY idx_logs_ip_created (ip_address, created_at),
  KEY idx_logs_entity (entity_type, entity_id),
  KEY idx_logs_user_created (user_id, created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Auditoria, erros, eventos de sistema e trilha operacional.';

CREATE TABLE backup_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_by BIGINT UNSIGNED NULL,
  frequency ENUM('daily', 'weekly') NOT NULL,
  filename VARCHAR(180) NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  sha256_hash CHAR(64) NULL,
  status ENUM('completed', 'failed') NOT NULL DEFAULT 'completed',
  includes_uploads TINYINT(1) NOT NULL DEFAULT 1,
  duration_ms INT UNSIGNED NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_backup_logs_frequency_created (frequency, created_at),
  KEY idx_backup_logs_status_created (status, created_at),
  KEY idx_backup_logs_created_by (created_by),
  CONSTRAINT fk_backup_logs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Historico seguro das rotinas de backup e retencao.';

-- Seeds operacionais minimos.
INSERT INTO pipeline_stages (name, slug, color, position) VALUES
  ('Novo diagnostico', 'novo-diagnostico', '#C8A95B', 10),
  ('Em analise', 'em-analise', '#D4AF37', 20),
  ('Proposta enviada', 'proposta-enviada', '#BDBDBD', 30),
  ('Agendado', 'agendado', '#72C78F', 40),
  ('Concluido', 'concluido', '#72C78F', 50)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  color = VALUES(color),
  position = VALUES(position);

INSERT INTO property_types (name, slug) VALUES
  ('Residencia', 'residencia'),
  ('Condominio', 'condominio'),
  ('Empresa', 'empresa'),
  ('Comercio', 'comercio')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO surface_types (name, slug, description) VALUES
  ('Garagem', 'garagem', 'Pisos de garagem e areas de manobra.'),
  ('Muro', 'muro', 'Muros internos e externos.'),
  ('Pedra', 'pedra', 'Pedras naturais, revestimentos e pisos nobres.'),
  ('Fachada', 'fachada', 'Fachadas, acessos e pontos de primeira impressao.'),
  ('Telhado', 'telhado', 'Telhados e coberturas.'),
  ('Piscina', 'piscina', 'Bordas, entorno e areas molhadas.'),
  ('Deck', 'deck', 'Decks e areas de convivencia.'),
  ('Area gourmet', 'area-gourmet', 'Espacos gourmet e areas externas sociais.'),
  ('Calcada', 'calcada', 'Calcadas, rampas e acessos.'),
  ('Outro', 'outro', 'Superficie nao catalogada.')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description);

INSERT INTO dirt_types (name, slug) VALUES
  ('Lodo', 'lodo'),
  ('Musgo', 'musgo'),
  ('Mofo', 'mofo'),
  ('Ferrugem', 'ferrugem'),
  ('Gordura', 'gordura'),
  ('Manchas', 'manchas')
ON DUPLICATE KEY UPDATE name = VALUES(name);
