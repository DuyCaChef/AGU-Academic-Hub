<?php

return [
    'community_ownership_download_threshold' => (int) env('DOCUMENT_COMMUNITY_OWNERSHIP_DOWNLOAD_THRESHOLD', 10),
    'trash_admin_delete_after_days' => (int) env('DOCUMENT_TRASH_ADMIN_DELETE_AFTER_DAYS', 60),
    'trash_auto_purge_after_days' => (int) env('DOCUMENT_TRASH_AUTO_PURGE_AFTER_DAYS', 90),
];
