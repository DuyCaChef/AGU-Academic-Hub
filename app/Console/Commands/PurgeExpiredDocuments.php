<?php

namespace App\Console\Commands;

use App\Models\LichSuThungRac;
use App\Models\TaiLieu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredDocuments extends Command
{
    protected $signature = 'documents:purge-trash {--days= : Số ngày trước khi hệ thống tự động xóa (mặc định theo cấu hình)}';

    protected $description = 'Xóa vĩnh viễn tài liệu và tệp đã quá hạn trong thùng rác';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('documents.trash_auto_purge_after_days'));
        $cutoff = now()->subDays($days);
        $count = 0;

        TaiLieu::onlyTrashed()
            ->where('ngay_xoa_tam', '<=', $cutoff)
            ->chunkById(100, function ($documents) use (&$count): void {
                foreach ($documents as $document) {
                    DB::transaction(function () use ($document): void {
                        LichSuThungRac::create([
                            'ma_tai_lieu' => $document->getKey(),
                            'hanh_dong' => 'PERMANENT_DELETE',
                            'nguon' => 'SYSTEM',
                            'ly_do' => 'Tự động xóa sau thời hạn lưu trong thùng rác.',
                            'chi_tiet' => ['duong_dan_tep' => $document->duong_dan_tep],
                        ]);
                        Storage::disk('local')->delete($document->duong_dan_tep);
                        $document->forceDelete();
                    });
                    $count++;
                }
            }, 'ma_tai_lieu');

        $this->info("Đã xóa vĩnh viễn {$count} tài liệu quá hạn {$days} ngày.");

        return self::SUCCESS;
    }
}
