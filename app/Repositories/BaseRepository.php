<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Filter otomatis semua field dari tabel
     */
    public function filterAll(Request $request)
    {

        $query = $this->model->newQuery();

        // Ambil semua kolom
        $columns = $this->model->getFillable();

        if (empty($columns)) {
            try {
                $columns = Schema::getColumnListing($this->model->getTable());
            } catch (\Exception $e) {
                $columns = [];
            }
        }

        foreach ($columns as $column) {
            if ($request->filled($column)) {
                $query->where($column, 'LIKE', '%' . $request->input($column) . '%');
            }
        }

        return $query;
    }

    /**
     * Pagination
     */
    public function paginate(Request $request, $query, int $perPage = 10)
    {
        $perPage = $request->get('per_page', $perPage);
        return $query->paginate($perPage);
    }
}
