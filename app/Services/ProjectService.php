<?php

namespace App\Services;

use App\Facades\Sqids;
use App\Models\Project;

class ProjectService
{
    public function findByEncodedId(string $encodedId)
    {
        $id = Sqids::decodeOrFail($encodedId);

        return Project::findOrFail($id);
    }
}
