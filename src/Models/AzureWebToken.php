<?php

namespace Yayasanvitka\AzureOauth2Validator\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AzureWebToken.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Models
 */
class AzureWebToken extends Model
{
    protected $table = 'user_web_tokens';

    protected $fillable = [
        'user_id',
        'ip_address',
        'session_id',
        'access_token',
        'refresh_token',
        'id_token',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
    ];

    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Define relation with users table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('\App\Models\User', 'user_id', 'id');
    }
}
