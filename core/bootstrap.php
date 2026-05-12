<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/app.php';

require_once __DIR__ . '/security/SessionService.php';
SessionService::start();

require_once __DIR__ . '/helpers/url.php';
require_once __DIR__ . '/helpers/view.php';
require_once __DIR__ . '/helpers/sanitize.php';
require_once __DIR__ . '/security/SecurityLogger.php';
require_once __DIR__ . '/security/CsrfService.php';
require_once __DIR__ . '/security/RateLimitService.php';
require_once __DIR__ . '/security/UploadSecurityService.php';
require_once __DIR__ . '/security/SecurityService.php';
require_once __DIR__ . '/security/csrf.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/auth/AuthService.php';
require_once __DIR__ . '/database/Connection.php';
require_once __DIR__ . '/api/ApiResponse.php';
require_once __DIR__ . '/api/ApiRequest.php';
require_once __DIR__ . '/api/ApiValidator.php';
require_once __DIR__ . '/api/ApiMiddleware.php';
require_once __DIR__ . '/api/ApiController.php';
require_once __DIR__ . '/api/controllers/ClientsController.php';
require_once __DIR__ . '/api/controllers/LeadsController.php';
require_once __DIR__ . '/api/controllers/UploadsController.php';
require_once __DIR__ . '/api/controllers/AiController.php';
require_once __DIR__ . '/api/controllers/CalendarController.php';
require_once __DIR__ . '/api/controllers/KanbanController.php';
require_once __DIR__ . '/api/controllers/ProductsController.php';
require_once __DIR__ . '/api/ApiRouter.php';
require_once __DIR__ . '/services/AiLogger.php';
require_once __DIR__ . '/services/OpenAiClient.php';
require_once __DIR__ . '/services/AiLeadContextService.php';
require_once __DIR__ . '/services/AiSummaryService.php';
require_once __DIR__ . '/services/AiTagService.php';
require_once __DIR__ . '/services/AiClassificationService.php';
require_once __DIR__ . '/services/AiJobService.php';
require_once __DIR__ . '/services/AiTextService.php';
require_once __DIR__ . '/services/AiVisualService.php';
require_once __DIR__ . '/services/AiQueueWorker.php';
require_once __DIR__ . '/services/ImageUploadService.php';
require_once __DIR__ . '/services/ImageHistoryService.php';
require_once __DIR__ . '/services/AiImageUsageService.php';
require_once __DIR__ . '/services/SettingsService.php';
require_once __DIR__ . '/services/DashboardAnalyticsService.php';
require_once __DIR__ . '/services/RecurrenceService.php';
require_once __DIR__ . '/services/VisualBankService.php';
require_once __DIR__ . '/services/ProductService.php';
require_once __DIR__ . '/services/NotificationService.php';
require_once __DIR__ . '/services/NoteService.php';
require_once __DIR__ . '/services/CacheService.php';
require_once __DIR__ . '/services/AssetService.php';
require_once __DIR__ . '/services/ImageOptimizationService.php';
require_once __DIR__ . '/services/SeoService.php';
require_once __DIR__ . '/services/BackupService.php';
require_once __DIR__ . '/services/AuditLogService.php';
require_once __DIR__ . '/services/QueueService.php';
require_once __DIR__ . '/services/QueueWorker.php';

date_default_timezone_set((string) ($config['app_timezone'] ?? 'America/Sao_Paulo'));
SecurityService::applyHeaders();
