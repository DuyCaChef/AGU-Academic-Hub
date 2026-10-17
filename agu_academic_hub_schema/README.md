# AGU Academic Hub - Database schema & Laravel migrations

## Target
- Laravel 11.x / PHP 8.2+
- MySQL 8.0, InnoDB
- Storage files in private/local or S3-compatible object storage; database stores metadata/path only.

## Install
1. Back up the current database.
2. Copy `database/migrations/*.php` into the Laravel project's `database/migrations/`.
3. Verify `.env` points to the intended MySQL 8.0 database.
4. Run `php artisan migrate:status`, then `php artisan migrate`.
5. Run `php artisan migrate:rollback` only in a disposable/dev environment if testing rollback.

The queue-table migration is optional: skip it if the project already has Laravel queue migrations or uses Redis queues. The Sanctum token migration is also unnecessary if the project already ran Sanctum's published migration.

This package contains schema migrations, not application services, Laravel Policies, queue jobs, controllers, models, file scanners, or signed-URL routes.

## Key decisions locked
1. A course may belong to multiple majors: `courses` ↔ `majors` via `course_majors`.
2. Binary deduplication: unique `(course_id, file_hash_sha256)`. Hash comparison is scoped to a course.
3. Version chain: `documents.parent_document_id` points to the immediately previous version. First version has `NULL`. To find the root, traverse ancestors. `version_label` is a display label and is not used as the version ordering key.
4. No document star-rating table and no `average_rating` column. Community-ownership logic uses `downloads_count >= 10`; the separate "rating >= 4" criterion in the source requirements cannot be implemented until a document-rating source is explicitly approved. Course-review ratings must not be reused as document ratings.
5. One review per user, course and academic term: unique `(course_id, user_id, academic_term)`. Example term values: `2026-1`, `2026-2`; application validation must enforce the chosen format.
6. Guests are unauthenticated visitors, not rows in `users`; `users.role` therefore contains only STUDENT, ALUMNI, MODERATOR, ADMIN.
7. Course review scores use MySQL CHECK constraints (requires MySQL 8.0.16+).
8. File hash unique constraint includes soft-deleted records. If a deleted document is restored, the application must resolve the hash collision or reuse/restore the existing row.
9. Reports are unique per `(document_id, reporter_id)` to prevent duplicate active reports. If repeat reports after resolution are required, change to a report-history model before production.
10. `document_similarity_matches`, `moderation_actions`, `audit_logs`, `document_processing_jobs`, `point_wallets`, and `point_transactions` are implementation-support tables added to support the specified workflows; they are not all explicitly enumerated as physical tables in the original design document.

## Important application-level invariants
- Use a DB transaction for bounty hold/release/payout. Lock wallet rows with `SELECT ... FOR UPDATE` and use a unique `idempotency_key` for each business event.
- Do not let clients set `downloads_count`, `likes_count`, similarity results, approval fields or wallet balances directly.
- A successful download should insert `document_downloads` and increment `documents.downloads_count` in one transaction.
- Upvote insert/delete and `likes_count` changes must be atomic.
- When creating a new version, lock the prior document row, set `parent_document_id` to the immediate prior version, and validate course consistency.
- Do not rely on a unique hash constraint alone to prevent duplicate physical uploads: check hash before storage write and handle concurrent requests/unique-key violations.
- Enforce 24-hour deletion window, zero-download rule, community ownership, report thresholds, roles and ownership in Laravel Policies/services, not just schema.
- KYC evidence must remain private and be served only through authorized routes or short-lived signed URLs.
- Public course-review serializers must never expose `user_id` for anonymous reviews. Store identity internally for moderation.
- Queue work (hashing, text extraction, similarity checks, malware scan, email) must be idempotent and retriable.
- `moderation_actions.target_type/target_id` and `audit_logs.target_type/target_id` are polymorphic references and cannot use conventional foreign keys to every target table.
- `notifications` uses Laravel-style polymorphic `notifiable` columns, so it can support users and other future notifiable models.

## Enum/status dictionary
- users.role: `STUDENT`, `ALUMNI`, `MODERATOR`, `ADMIN`
- users.status: `UNVERIFIED`, `ACTIVE`, `SUSPENDED`
- verification_requests.status: `PENDING`, `APPROVED`, `REJECTED`
- documents.status: `PENDING_REVIEW`, `PUBLISHED`, `HIDDEN_FOR_REVIEW`, `REJECTED`, `SUSPENDED`
- documents.category / document_requests.category: `EXAM`, `LECTURE`, `PROJECT`, `EXERCISE`, `QUIZ`, `OTHER`
- documents.similarity_status: `NOT_CHECKED`, `QUEUED`, `CHECKED`, `MATCH_FOUND`, `FAILED`
- document_reports.status: `PENDING`, `RESOLVED`, `DISMISSED`
- document_removal_requests.status: `PENDING`, `APPROVED`, `REJECTED`
- course_reviews.status: `PENDING`, `PUBLISHED`, `HIDDEN`, `REJECTED`
- document_requests.status: `OPEN`, `FULFILLED`, `CLOSED`, `CANCELLED`
- document_request_fulfillments.status: `PENDING`, `ACCEPTED`, `REJECTED`
- point_transactions.transaction_type: `EARN`, `HOLD`, `RELEASE`, `SPEND`, `REFUND`

## Main entity relationship summary
- `faculties` 1:N `majors`; `faculties` 1:N `users`
- `majors` M:N `courses` via `course_majors`
- `courses` 1:N `documents`, `course_reviews`, `document_requests`
- `documents` 1:N version chain via `parent_document_id`
- `documents` 1:N downloads, upvotes, reports, removal requests, processing jobs
- `users` 1:N uploads, reviews, reports, KYC requests, document requests
- `users` 1:1 `point_wallets`; wallets 1:N point transactions
- `document_requests` 1:N fulfillment submissions; an accepted fulfillment must be settled once by application logic
