<?php

final class ProductsController extends ApiController
{
    public function index(ApiRequest $request): void
    {
        $service = new ProductService($this->pdo);

        ApiResponse::success([
            'products' => $service->catalog(clean_text($request->query['q'] ?? '')),
            'categories' => ProductService::categories(),
        ]);
    }

    public function store(ApiRequest $request): void
    {
        $data = $request->input();
        ApiValidator::assert($data, ['name' => 'required|max:160']);

        $productId = (new ProductService($this->pdo))->saveProduct($data, auth_id());
        $this->audit->write('operational', 'info', 'api_product_saved', 'Produto salvo via API interna.', [], auth_id(), 'product', $productId);

        ApiResponse::success(['product_id' => $productId], 'Produto salvo.', 201);
    }

    public function usage(ApiRequest $request, ?int $id = null): void
    {
        $data = $request->input();
        if ($id) {
            $data['product_id'] = $id;
        }

        $usageId = (new ProductService($this->pdo))->registerUsage($data, auth_id());
        $this->audit->write('operational', 'info', 'api_product_usage_created', 'Uso de produto registrado via API interna.', [], auth_id(), 'product_usage', $usageId);

        ApiResponse::success(['usage_id' => $usageId], 'Uso registrado.', 201);
    }
}
