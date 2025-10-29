<?php

namespace App\Models\Hardware;

use App\Models\Hardware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class HardwareStatus extends Model
{
    use SoftDeletes, HasUuid;

    protected $table = "master_hardware_statuses";

    protected $fillable = [
        'level',
        'name',
        'description'
    ];

    public function hardwares(): HasMany
    {
        return $this->hasMany(Hardware::class, 'status_id');
    }
}
