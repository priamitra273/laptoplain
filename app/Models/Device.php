<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory, SoftDeletes, HasUuid, LogUsers;

    protected $fillable = [
        'uuid',
        'mac_address',
        'ip_dhcp',
        'ip_static'
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function cctv(): HasOne
    {
        return $this->hasOne(Cctv::class);
    }
}
