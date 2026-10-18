<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'faculties';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}