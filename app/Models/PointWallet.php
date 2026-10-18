<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointWallet extends Model
{
    use HasFactory;

    protected $table = 'point_wallets';

    protected $fillable = [
        'user_id',
        'available_points',
        'held_points',
    ];

    protected function casts(): array
    {
        return [
            'available_points' => 'integer',
            'held_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'wallet_id');
    }
}