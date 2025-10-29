<?php

namespace App\Models;

use App\Models\Hardware\Brand;
use App\Models\Hardware\HardwareStatus;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Hardware extends Model
{
    use SoftDeletes, HasUuid, LogUsers;

    protected $table = 'master_hardware';

    protected $fillable = [
        'po_number',
        'serial_number',
        'category',
        'remarks',
        'component_id',
        'brand_id',
        'model'
    ];

    public function serversAsCpu(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'cpu_id');
    }

    public function serversAsGpu(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'gpu_id');
    }

    public function serversAsRam(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'ram_id');
    }

    public function serversAsSsd(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'ssd_id');
    }

    public function serversAsMotherboard(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'mobo_id');
    }

    public function serversAsNetworkCard(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'nic_id');
    }

    public function serversAsPowerSupply(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'psu_id');
    }

    public function serversAsLiquidCooling(): HasMany
    {
        return $this->hasMany(AnalyticServer::class, 'lc_id');
    }

    public function getAllServersUsingThisHardware()
    {
        return collect([
            $this->serversAsCpu,
            $this->serversAsGpu,
            $this->serversAsRam,
            $this->serversAsSsd,
            $this->serversAsMotherboard,
            $this->serversAsNetworkCard,
            $this->serversAsPowerSupply,
            $this->serversAsLiquidCooling,
        ])->flatten()->unique('id');
    }

    // Scope to filter by category
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope the query to only get unused items.
     * 
     */
    protected function scopeUnused(Builder $query)
    {
        return $query->doesntHave('serversAsCpu')
            ->doesntHave('serversAsGpu')
            ->doesntHave('serversAsRam')
            ->doesntHave('serversAsSsd')
            ->doesntHave('serversAsMotherboard')
            ->doesntHave('serversAsNetworkCard')
            ->doesntHave('serversAsPowerSupply')
            ->doesntHave('serversAsLiquidCooling');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(HardwareComponent::class, 'component_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(HardwareStatus::class, 'status_id');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
