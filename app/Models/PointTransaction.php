<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    public $timestamps = false;
    protected $table = 'point_transactions';

    protected $fillable = [
        'wallet_id',
        'transaction_type',
        'points',
        'available_delta',
        'held_delta',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'available_delta' => 'integer',
            'held_delta' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PointWallet::class, 'wallet_id');
    }
}