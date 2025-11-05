<?php

namespace App\Repositories;

use App\Models\MsProjectPriority;
use Illuminate\Http\Request;

class MsProjectPriorityRepository extends BaseRepository
{
    public function __construct(MsProjectPriority $model)
    {
        parent::__construct($model);
    }

    public function getList(Request $request)
    {
        $query = $this->filterAll($request);
        return $this->paginate($request, $query);
    }
}
