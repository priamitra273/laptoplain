<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class AnalyticServer extends Model
{
    use SoftDeletes, HasUuid, LogUsers;

    protected $fillable = [
        'ip_address',
        'lisence',
        'is_alive',
        'is_active',
        'cpu_id',
        'gpu_id',
        'ram_id',
        'ssd_id',
        'mobo_id',
        'nic_id',
        'psu_id',
        'lc_id',
    ];

    public function cctv(): HasMany
    {
        return $this->hasMany(Cctv::class);
    }

    public function maintenance_logs(): HasMany
    {
        return $this->hasMany(MaintenanceServerLog::class);
    }

    public function cpu(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'cpu_id');
    }

    public function gpu(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'gpu_id');
    }

    public function ram(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'ram_id');
    }

    public function ssd(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'ssd_id');
    }

    public function motherboard(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'mobo_id');
    }

    public function nic(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'nic_id');
    }

    public function psu(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'psu_id');
    }

    public function lc(): BelongsTo
    {
        return $this->belongsTo(Hardware::class, 'lc_id');
    }

    public function getAllHardware()
    {
        return [
            'cpu' => $this->cpu,
            'gpu' => $this->gpu,
            'ram' => $this->ram,
            'ssd' => $this->ssd,
            'motherboard' => $this->motherboard,
            'network_card' => $this->networkCard,
            'power_supply' => $this->powerSupply,
            'liquid_cooling' => $this->liquidCooling,
        ];
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
