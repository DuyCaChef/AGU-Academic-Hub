<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseReviewVote extends Model
{
    protected $table = 'course_review_votes';

    protected $fillable = [
        'review_id',
        'user_id',
        'is_helpful',
    ];

    protected function casts(): array
    {
        return [
            'is_helpful' => 'boolean',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(CourseReview::class, 'review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}