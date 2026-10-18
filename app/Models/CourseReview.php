<?php

namespace App\Models;

use App\Enums\CourseReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course_reviews';

    protected $fillable = [
        'course_id',
        'user_id',
        'academic_term',
        'difficulty_score',
        'practicality_score',
        'workload_score',
        'content',
        'is_anonymous',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'difficulty_score' => 'integer',
            'practicality_score' => 'integer',
            'workload_score' => 'integer',
            'is_anonymous' => 'boolean',
            'status' => CourseReviewStatus::class,
        ];
    }

    // --- Relationships ---

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CourseReviewVote::class, 'review_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CourseReviewReport::class, 'review_id');
    }

    // Scope lấy review hiển thị công khai
    public function scopePublished($query)
    {
        return $query->where('status', CourseReviewStatus::PUBLISHED);
    }
}