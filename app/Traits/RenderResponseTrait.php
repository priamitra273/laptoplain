<?php

namespace App\Traits;

use Inertia\Inertia;
use Illuminate\Http\JsonResponse;

trait RenderResponseTrait
{
    protected function renderInertia(string $component, $data, string $key = 'items')
    {
        $meta = null;

        if (method_exists($data, 'total')) {
            $meta = [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ];
            $data = collect($data->items())->map(fn($item) => $item->toArray())->all();
        } elseif ($data instanceof \Illuminate\Database\Eloquent\Collection) {
            $data = $data->map(fn($item) => $item->toArray())->all();
        } elseif ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        }

        return Inertia::render($component, [
            $key => $data,
            'meta' => $meta,
        ]);
    }

    protected function respond($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function showData($data): JsonResponse
    {
        return $this->respond($data);
    }
}
