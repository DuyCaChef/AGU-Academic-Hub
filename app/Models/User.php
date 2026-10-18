<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'faculty_id',
        'full_name',
        'email',
        'student_code',
        'password',
        'role',
        'status',
        'reputation_score',
        'avatar_path',
        'email_verified_at',
        'graduation-year',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'reputation_score' => 'integer',
            'graduation_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Tự động khởi tạo ví điểm cho người dùng mới
            $user->pointWallet()->create([
                'available_points' => 100,
                'held_points' => 0,
            ]);
        });
    }

    // --- Relationships ---
    public function faculy(): BelongsTo
    {
        return $this->BelongsTo(Faculty::class);
    }

    public function pointWallet(): HasOne
    {
        return $this->hasOne(PointWallet::class);
    }

    public function coutseReviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class, 'creator_id');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(UploadedDocument::class, 'uploader_id');
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    // --- Role Helpers ---
    public function isAdmin(): bool
    {

        return $this->role === UserRole::ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::MODERATOR;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }
}

