<?php

return [
    'trusted_reporter_reputation' => (int) env('MODERATION_TRUSTED_REPORTER_REPUTATION', 100),
    'review_report_threshold' => (int) env('MODERATION_REVIEW_REPORT_THRESHOLD', 3),
    'auto_hide_report_threshold' => (int) env('MODERATION_AUTO_HIDE_REPORT_THRESHOLD', 5),
];
