<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthRefreshToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_refresh_tokens';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'access_token_id',
        'token',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(OAuthAccessToken::class, 'access_token_id');
    }
}
