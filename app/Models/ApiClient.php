<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'client_id',
        'client_secret_hash',
        'abilities',
        'is_active',
        'last_used_at',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(ApiRefreshToken::class);
    }

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
