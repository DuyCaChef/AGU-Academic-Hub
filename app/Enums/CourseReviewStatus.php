<?php

namespace App\Enums;

enum CoursesReviewStatus: string
{
    case PENDING = 'PENDING';
    case PUBLISHED = 'PUBLISHED';
    case HIDDEN = 'HIDDEN';
    case REJECTED = 'REJECTED';
}