<?php

namespace App\Modules\Moderation\Infrastructure\Persistence;

use App\Models\BaoCaoDanhGia;
use App\Models\BaoCaoViPham;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Collection as BaseCollection;

class ReviewModerationRepository
{
    // Phân trang danh sách báo cáo đánh giá môn học kèm bộ lọc (Tương thích nghiệp vụ review)
    public function paginateReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return BaoCaoDanhGia::query()
            ->with([
                'danhGia' => fn($q) => $q->withTrashed()->with(['monHoc', 'nguoiDung']),
                'nguoiBaoCao',
                'nguoiXuLy',
            ])
            ->when(!empty($filters['status']), fn(Builder $query) => $query->where('trang_thai', $filters['status']))
            ->when(!empty($filters['reason']), fn(Builder $query) => $query->where('ly_do', $filters['reason']))
            ->when(!empty($filters['from']), fn(Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn(Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when(!empty($filters['search']), function (Builder $query) use ($filters) {
                $term = '%' . trim($filters['search']) . '%';
                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('chi_tiet', 'like', $term)
                        ->orWhere('ten_giang_vien', 'like', $term)
                        ->orWhereHas('danhGia', function (Builder $dq) use ($term) {
                            $dq->where('noi_dung', 'like', $term)
                               ->orWhereHas('monHoc', function (Builder $mq) use ($term) {
                                   $mq->where('ma_mon_hoc', 'like', $term)
                                      ->orWhere('ten_mon_hoc', 'like', $term);
                               });
                        })
                        ->orWhereHas('nguoiBaoCao', fn(Builder $uq) => $uq->where('ho_ten', 'like', $term));
                });
            })
            // Ưu tiên báo cáo chờ xử lý lên đầu
            ->orderByRaw("CASE WHEN trang_thai = 'CHO_XU_LY' THEN 0 ELSE 1 END")
            ->latest('ma_bao_cao')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Phân trang hàng đợi báo cáo thống nhất (Bao gồm Đánh giá Môn học, Giảng viên và Tài liệu)
    public function paginateUnifiedReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $selectedType = $filters['type'] ?? 'ALL';
        $items = new BaseCollection();

        // 1. Lấy dữ liệu báo cáo đánh giá (Môn học hoặc Giảng viên) nếu phù hợp bộ lọc
        if (in_array($selectedType, ['ALL', 'MON_HOC', 'GIANG_VIEN'], true)) {
            $reviewQuery = BaoCaoDanhGia::query()
                ->with([
                    'danhGia' => fn($q) => $q->withTrashed()->with(['monHoc', 'nguoiDung']),
                    'nguoiBaoCao',
                    'nguoiXuLy',
                ])
                ->when($selectedType === 'MON_HOC', function (Builder $q) {
                    $q->where(function (Builder $sub) {
                        $sub->where('loai_doi_tuong', 'MON_HOC')->orWhereNull('loai_doi_tuong');
                    });
                })
                ->when($selectedType === 'GIANG_VIEN', fn(Builder $q) => $q->where('loai_doi_tuong', 'GIANG_VIEN'))
                ->when(!empty($filters['status']), fn(Builder $q) => $q->where('trang_thai', $filters['status']))
                ->when(!empty($filters['reason']), fn(Builder $q) => $q->where('ly_do', $filters['reason']))
                ->when(!empty($filters['from']), fn(Builder $q) => $q->whereDate('created_at', '>=', $filters['from']))
                ->when(!empty($filters['to']), fn(Builder $q) => $q->whereDate('created_at', '<=', $filters['to']))
                ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                    $term = '%' . trim($filters['search']) . '%';
                    $q->where(function (Builder $sub) use ($term) {
                        $sub->where('chi_tiet', 'like', $term)
                            ->orWhere('ten_giang_vien', 'like', $term)
                            ->orWhereHas('danhGia', function (Builder $dq) use ($term) {
                                $dq->where('noi_dung', 'like', $term)
                                   ->orWhereHas('monHoc', function (Builder $mq) use ($term) {
                                       $mq->where('ma_mon_hoc', 'like', $term)
                                          ->orWhere('ten_mon_hoc', 'like', $term);
                                   });
                            })
                            ->orWhereHas('nguoiBaoCao', fn(Builder $uq) => $uq->where('ho_ten', 'like', $term));
                    });
                });

            $reviewReports = $reviewQuery->latest('ma_bao_cao')->get();

            foreach ($reviewReports as $rep) {
                $rev = $rep->danhGia;
                $course = $rev?->monHoc;
                $isLecturer = ($rep->loai_doi_tuong === 'GIANG_VIEN') || ($rep->ly_do === 'BOI_NHO');
                $typeKey = $isLecturer ? 'GIANG_VIEN' : 'MON_HOC';

                $items->push([
                    'ma_bao_cao'       => $rep->ma_bao_cao,
                    'unique_id'        => 'review_' . $rep->ma_bao_cao,
                    'loai_doi_tuong'   => $typeKey,
                    'loai_nhan'        => $isLecturer ? 'Đánh giá Giảng viên' : 'Đánh giá Môn học',
                    'ma_mon_hoc'       => $course?->ma_mon_hoc ?? '—',
                    'ten_mon_hoc'      => $course?->ten_mon_hoc ?? ('Đánh giá #' . $rep->ma_danh_gia),
                    'ten_giang_vien'   => $rep->ten_giang_vien,
                    'noi_dung_chinh'   => $rev?->noi_dung ?? '',
                    'hoc_ky'           => $rev?->hoc_ky,
                    'diem_do_kho'      => $rev?->diem_do_kho ?? 0,
                    'an_danh'          => (bool) ($rev?->an_danh ?? false),
                    'ly_do'            => $rep->ly_do,
                    'chi_tiet'         => $rep->chi_tiet,
                    'trang_thai'       => $rep->trang_thai,
                    'ghi_chu_xu_ly'    => $rep->ghi_chu_xu_ly,
                    'thoi_gian_xu_ly'  => $rep->thoi_gian_xu_ly?->toIso8601String(),
                    'nguoi_bao_cao'    => $rep->nguoiBaoCao ? [
                        'ma_nguoi_dung' => $rep->nguoiBaoCao->ma_nguoi_dung,
                        'ho_ten'        => $rep->nguoiBaoCao->ho_ten,
                        'mssv'          => $rep->nguoiBaoCao->mssv,
                    ] : null,
                    'nguoi_xu_ly'      => $rep->nguoiXuLy ? [
                        'ma_nguoi_dung' => $rep->nguoiXuLy->ma_nguoi_dung,
                        'ho_ten'        => $rep->nguoiXuLy->ho_ten,
                    ] : null,
                    'created_at'       => $rep->created_at?->toIso8601String(),
                    'created_at_raw'   => $rep->created_at,
                    'url_xem_ngoai'    => $course ? route('courses.reviews.index', $course->ma_mon_hoc) : null,
                    'url_chi_tiet'     => route('admin.review-reports.show', $rep->ma_bao_cao),
                    'url_luu_tru'      => route('admin.review-reports.archive', $rep->ma_bao_cao),
                    'la_tai_lieu'      => false,
                    'danh_gia'         => $rev,
                ]);
            }
        }

        // 2. Lấy dữ liệu báo cáo tài liệu nếu phù hợp bộ lọc
        if (in_array($selectedType, ['ALL', 'TAI_LIEU'], true)) {
            $docQuery = BaoCaoViPham::query()
                ->with([
                    'taiLieu' => fn($q) => $q->withTrashed()->with('monHoc'),
                    'nguoiBaoCao',
                    'nguoiXuLy',
                ])
                ->when(!empty($filters['status']), fn(Builder $q) => $q->where('trang_thai_xu_ly', $filters['status']))
                ->when(!empty($filters['reason']), fn(Builder $q) => $q->where('ly_do', $filters['reason']))
                ->when(!empty($filters['from']), fn(Builder $q) => $q->whereDate('created_at', '>=', $filters['from']))
                ->when(!empty($filters['to']), fn(Builder $q) => $q->whereDate('created_at', '<=', $filters['to']))
                ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                    $term = '%' . trim($filters['search']) . '%';
                    $q->where(function (Builder $sub) use ($term) {
                        $sub->where('chi_tiet_loi', 'like', $term)
                            ->orWhereHas('taiLieu', function (Builder $tq) use ($term) {
                                $tq->where('tieu_de', 'like', $term)
                                   ->orWhereHas('monHoc', function (Builder $mq) use ($term) {
                                       $mq->where('ma_mon_hoc', 'like', $term)
                                          ->orWhere('ten_mon_hoc', 'like', $term);
                                   });
                            })
                            ->orWhereHas('nguoiBaoCao', fn(Builder $uq) => $uq->where('ho_ten', 'like', $term));
                    });
                });

            $docReports = $docQuery->latest('ma_bao_cao')->get();

            foreach ($docReports as $rep) {
                $doc = $rep->taiLieu;
                $course = $doc?->monHoc;

                $items->push([
                    'ma_bao_cao'       => $rep->ma_bao_cao,
                    'unique_id'        => 'doc_' . $rep->ma_bao_cao,
                    'loai_doi_tuong'   => 'TAI_LIEU',
                    'loai_nhan'        => 'Tài liệu học thuật',
                    'ma_mon_hoc'       => $course?->ma_mon_hoc ?? ('TL' . $rep->ma_tai_lieu),
                    'ten_mon_hoc'      => $doc?->tieu_de ?? ('Tài liệu #' . $rep->ma_tai_lieu),
                    'ten_giang_vien'   => null,
                    'noi_dung_chinh'   => $rep->chi_tiet_loi ?? ($doc?->mo_ta ?? ''),
                    'hoc_ky'           => null,
                    'diem_do_kho'      => null,
                    'an_danh'          => false,
                    'dung_luong'       => $doc?->kich_thuoc_tep,
                    'loai_tep'         => $doc?->loai_tep,
                    'ly_do'            => $rep->ly_do,
                    'chi_tiet'         => $rep->chi_tiet_loi,
                    'trang_thai'       => $rep->trang_thai_xu_ly,
                    'ghi_chu_xu_ly'    => $rep->ly_do_xu_ly,
                    'thoi_gian_xu_ly'  => $rep->xu_ly_luc?->toIso8601String(),
                    'nguoi_bao_cao'    => $rep->nguoiBaoCao ? [
                        'ma_nguoi_dung' => $rep->nguoiBaoCao->ma_nguoi_dung,
                        'ho_ten'        => $rep->nguoiBaoCao->ho_ten,
                        'mssv'          => $rep->nguoiBaoCao->mssv,
                    ] : null,
                    'nguoi_xu_ly'      => $rep->nguoiXuLy ? [
                        'ma_nguoi_dung' => $rep->nguoiXuLy->ma_nguoi_dung,
                        'ho_ten'        => $rep->nguoiXuLy->ho_ten,
                    ] : null,
                    'created_at'       => $rep->created_at?->toIso8601String(),
                    'created_at_raw'   => $rep->created_at,
                    'url_xem_ngoai'    => null,
                    'url_chi_tiet'     => route('admin.reports.show', $rep->ma_bao_cao),
                    'url_luu_tru'      => route('admin.reports.archive', $rep->ma_bao_cao),
                    'la_tai_lieu'      => true,
                    'danh_gia'         => null,
                ]);
            }
        }

        // 3. Sắp xếp danh sách: Ưu tiên CHỜ XỬ LÝ lên đầu, sau đó theo thời gian giảm dần
        $sorted = $items->sort(function ($a, $b) {
            $isPendingA = ($a['trang_thai'] === 'CHO_XU_LY') ? 0 : 1;
            $isPendingB = ($b['trang_thai'] === 'CHO_XU_LY') ? 0 : 1;

            if ($isPendingA !== $isPendingB) {
                return $isPendingA <=> $isPendingB;
            }

            $timeA = $a['created_at_raw'] ? $a['created_at_raw']->timestamp : 0;
            $timeB = $b['created_at_raw'] ? $b['created_at_raw']->timestamp : 0;

            return $timeB <=> $timeA;
        })->values();

        // 4. Phân trang bằng Concrete LengthAwarePaginator
        $page = (int) (request('page') ?: 1);
        $total = $sorted->count();
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new ConcretePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginator;
    }

    // Tìm hồ sơ báo cáo theo ID kèm các quan hệ liên kết
    public function findReportById(int $id): BaoCaoDanhGia
    {
        return BaoCaoDanhGia::query()
            ->with([
                'danhGia' => fn($q) => $q->withTrashed()->with(['monHoc', 'nguoiDung']),
                'nguoiBaoCao',
                'nguoiXuLy',
            ])
            ->findOrFail($id);
    }

    // Lấy danh sách các báo cáo khác cùng hướng tới bài đánh giá này
    public function getRelatedReports(?int $reviewId, int $excludeReportId): Collection
    {
        if (!$reviewId) {
            return new Collection();
        }

        return BaoCaoDanhGia::query()
            ->with(['nguoiBaoCao', 'nguoiXuLy'])
            ->where('ma_danh_gia', $reviewId)
            ->where('ma_bao_cao', '!=', $excludeReportId)
            ->latest('ma_bao_cao')
            ->get();
    }

    // Thống kê toàn diện số lượng báo cáo vi phạm theo đối tượng và trạng thái
    public function getUnifiedStats(): array
    {
        $courseTotal = BaoCaoDanhGia::query()->where(function ($q) {
            $q->where('loai_doi_tuong', 'MON_HOC')->orWhereNull('loai_doi_tuong');
        })->count();

        $lecturerTotal = BaoCaoDanhGia::query()->where('loai_doi_tuong', 'GIANG_VIEN')->count();
        $docTotal = BaoCaoViPham::query()->count();

        $coursePending = BaoCaoDanhGia::query()
            ->where('trang_thai', 'CHO_XU_LY')
            ->where(function ($q) {
                $q->where('loai_doi_tuong', 'MON_HOC')->orWhereNull('loai_doi_tuong');
            })
            ->count();

        $lecturerPending = BaoCaoDanhGia::query()
            ->where('trang_thai', 'CHO_XU_LY')
            ->where('loai_doi_tuong', 'GIANG_VIEN')
            ->count();

        $docPending = BaoCaoViPham::query()->where('trang_thai_xu_ly', 'CHO_XU_LY')->count();

        $reviewResolved = BaoCaoDanhGia::query()->where('trang_thai', 'DA_XU_LY')->count();
        $docResolved = BaoCaoViPham::query()->where('trang_thai_xu_ly', 'DA_XU_LY')->count();

        $reviewDismissed = BaoCaoDanhGia::query()->where('trang_thai', 'BAC_BO')->count();
        $docDismissed = BaoCaoViPham::query()->where('trang_thai_xu_ly', 'BAC_BO')->count();

        return [
            'total'             => $courseTotal + $lecturerTotal + $docTotal,
            'pending'           => $coursePending + $lecturerPending + $docPending,
            'resolved'          => $reviewResolved + $docResolved,
            'dismissed'         => $reviewDismissed + $docDismissed,
            'course_count'      => $courseTotal,
            'lecturer_count'    => $lecturerTotal,
            'document_count'    => $docTotal,
            'course_pending'    => $coursePending,
            'lecturer_pending'  => $lecturerPending,
            'document_pending'  => $docPending,
        ];
    }

    // Thống kê nhanh số lượng báo cáo đánh giá theo trạng thái (Tương thích ngược)
    public function getStatusStats(): array
    {
        return [
            'total'      => BaoCaoDanhGia::query()->count(),
            'pending'    => BaoCaoDanhGia::query()->where('trang_thai', 'CHO_XU_LY')->count(),
            'resolved'   => BaoCaoDanhGia::query()->where('trang_thai', 'DA_XU_LY')->count(),
            'dismissed'  => BaoCaoDanhGia::query()->where('trang_thai', 'BAC_BO')->count(),
        ];
    }
}
