<?php

namespace App\Models\Hardware;

use App\Models\Hardware;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Brand extends Model
{
    use SoftDeletes, LogUsers, HasUuid;

    protected $table = "master_hardware_brands";

    protected $fillable = [
        'name'
    ];

    public function hardwares(): HasMany
    {
        return $this->hasMany(Hardware::class);
    }

    protected function name(): Attribute
    {
        return Attribute::set(fn(string $value) => ucwords($value));
    }
}
