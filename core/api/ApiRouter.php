<?php

final class ApiRouter
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private SecurityLogger $logger
    ) {
    }

    public function dispatch(ApiRequest $request): void
    {
        $audit = new AuditLogService($this->pdo);
        $middleware = new ApiMiddleware($this->pdo, $this->logger);
        $route = $this->match($request);

        if (!$route) {
            ApiResponse::error('Rota nao encontrada.', 404);
        }

        [$permission, $controllerClass, $action, $params] = $route;
        $middleware->handle($request, $permission);

        try {
            $controller = new $controllerClass($this->pdo, $this->config, $this->logger, $audit);
            $controller->{$action}($request, ...$params);
        } catch (InvalidArgumentException $exception) {
            ApiResponse::error($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            $this->logger->log('error', 'internal_api_error', 'Erro na API interna.', [
                'path' => $request->path,
                'method' => $request->method,
                'error' => $exception->getMessage(),
                'user_id' => auth_id(),
            ]);
            ApiResponse::error('Nao foi possivel processar a requisicao.', 500);
        }
    }

    private function match(ApiRequest $request): ?array
    {
        $path = trim($request->path, '/');
        $segments = $path === '' ? [] : explode('/', $path);
        $resource = $segments[0] ?? '';
        $id = isset($segments[1]) && ctype_digit($segments[1]) ? (int) $segments[1] : null;
        $sub = $segments[2] ?? null;

        return match ($resource) {
            'clients' => $this->resource($request, 'crm', ClientsController::class, $id),
            'leads' => $this->resource($request, 'crm', LeadsController::class, $id),
            'uploads' => $this->uploads($request),
            'ai' => $this->ai($request, $segments),
            'calendar' => $this->resource($request, 'calendar', CalendarController::class, $id),
            'kanban' => $request->method === 'POST' && (($segments[1] ?? '') === 'move' || $sub === null) ? ['kanban', KanbanController::class, 'move', []] : null,
            'products' => $this->products($request, $id, $sub),
            default => null,
        };
    }

    private function resource(ApiRequest $request, string $permission, string $controller, ?int $id): ?array
    {
        if ($request->method === 'GET' && $id === null) {
            return [$permission, $controller, 'index', []];
        }

        if ($request->method === 'GET' && $id !== null) {
            return [$permission, $controller, 'show', [$id]];
        }

        if ($request->method === 'POST' && $id === null) {
            return [$permission, $controller, 'store', []];
        }

        if (in_array($request->method, ['PUT', 'PATCH', 'POST'], true) && $id !== null) {
            return [$permission, $controller, 'update', [$id]];
        }

        return null;
    }

    private function uploads(ApiRequest $request): ?array
    {
        return match ($request->method) {
            'GET' => ['uploads', UploadsController::class, 'index', []],
            'POST' => ['uploads', UploadsController::class, 'store', []],
            default => null,
        };
    }

    private function ai(ApiRequest $request, array $segments): ?array
    {
        if ($request->method !== 'POST') {
            return null;
        }

        return match ($segments[1] ?? '') {
            'text' => ['crm', AiController::class, 'text', []],
            'visual' => ['uploads', AiController::class, 'visual', []],
            default => null,
        };
    }

    private function products(ApiRequest $request, ?int $id, ?string $sub): ?array
    {
        if ($request->method === 'GET') {
            return ['products', ProductsController::class, 'index', []];
        }

        if ($request->method === 'POST' && $sub === 'usage') {
            return ['products', ProductsController::class, 'usage', [$id]];
        }

        if ($request->method === 'POST') {
            return ['products', ProductsController::class, 'store', []];
        }

        return null;
    }
}
