<?php

namespace App\Models;

use App\Enums\DocumentRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'document_requests';

    protected $fillable = [
        'course_id',
        'creator_id',
        'title',
        'description',
        'lecturer_name',
        'category',
        'bounty_points',
        'status',
        'fulfilled_document_id',
        'confirmed_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'bounty_points' => 'integer',
            'status' => DocumentRequestStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    // --- Relationships ---
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function fulfilledDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'fulfilled_document_id');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(DocumentRequestFulfillment::class, 'request_id');
    }
}