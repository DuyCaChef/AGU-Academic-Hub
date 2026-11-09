<?php

return [
    'trusted_reporter_reputation' => (int) env('MODERATION_TRUSTED_REPORTER_REPUTATION', 100),
    'review_report_threshold' => (int) env('MODERATION_REVIEW_REPORT_THRESHOLD', 3),
    'auto_hide_report_threshold' => (int) env('MODERATION_AUTO_HIDE_REPORT_THRESHOLD', 5),
    'document_violation_penalty_points' => (int) env('MODERATION_DOC_VIOLATION_PENALTY_POINTS', 20),
    'review_violation_penalty_points' => (int) env('MODERATION_REVIEW_VIOLATION_PENALTY_POINTS', 10),
];
