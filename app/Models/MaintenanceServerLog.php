<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceServerLog extends Model
{
    use SoftDeletes, LogUsers;

    protected $fillable = [
        'analytic_server_id',
        'cpu_id',
        'gpu_id',
        'ram_id',
        'ssd_id',
        'mobo_id',
        'nic_id',
        'psu_id',
        'lc_id'
    ];

    public function analytic_server(): BelongsTo
    {
        return $this->belongsTo(AnalyticServer::class);
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

    public function mobo(): BelongsTo
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
}
