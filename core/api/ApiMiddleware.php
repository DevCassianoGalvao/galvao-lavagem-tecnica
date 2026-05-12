<?php

final class ApiMiddleware
{
    public function __construct(
        private PDO $pdo,
        private SecurityLogger $logger
    ) {
    }

    public function handle(ApiRequest $request, string $permission = 'dashboard'): void
    {
        SecurityService::applyHeaders();
        require_auth();

        if (!auth_can($permission)) {
            $this->logger->log('warning', 'api_permission_denied', 'Permissao negada na API interna.', [
                'path' => $request->path,
                'permission' => $permission,
                'user_id' => auth_id(),
            ]);
            ApiResponse::error('Permissao insuficiente.', 403);
        }

        if (!in_array($request->method, ['GET', 'HEAD'], true)) {
            $token = $request->value('_csrf_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

            if (!CsrfService::validate($token)) {
                $this->logger->log('warning', 'api_csrf_failed', 'CSRF invalido na API interna.', [
                    'path' => $request->path,
                    'user_id' => auth_id(),
                ]);
                ApiResponse::error('Token de seguranca invalido.', 419);
            }
        }

        $identity = 'api|' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . (auth_id() ?? session_id());

        if (!(new RateLimitService($this->pdo, $this->logger))->hit('internal_api', 240, 3600, $identity)) {
            ApiResponse::error('Muitas tentativas. Aguarde alguns instantes.', 429);
        }
    }
}
