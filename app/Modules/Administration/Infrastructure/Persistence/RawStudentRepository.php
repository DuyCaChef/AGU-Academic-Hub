<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Models\DanhSachSinhVienRaw;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class RawStudentRepository
{
    /**
     * Lấy danh sách sinh viên thô có phân trang và bộ lọc.
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DanhSachSinhVienRaw::query()->orderBy('mssv');

        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('mssv', 'like', "%{$keyword}%")
                    ->orWhere('ho_ten', 'like', "%{$keyword}%")
                    ->orWhere('email_sv', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['da_kich_hoat']) && $filters['da_kich_hoat'] !== '') {
            $query->where('da_kich_hoat', filter_var($filters['da_kich_hoat'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['ma_khoa'])) {
            $query->where('ma_khoa', $filters['ma_khoa']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?DanhSachSinhVienRaw
    {
        return DanhSachSinhVienRaw::find($id);
    }

    public function findByMssv(string $mssv): ?DanhSachSinhVienRaw
    {
        return DanhSachSinhVienRaw::timTheoMssv($mssv);
    }

    public function create(array $data): DanhSachSinhVienRaw
    {
        if (!empty($data['mat_khau_mac_dinh']) && !str_starts_with($data['mat_khau_mac_dinh'], '$2y$')) {
            $data['mat_khau_mac_dinh'] = Hash::make($data['mat_khau_mac_dinh']);
        }

        return DanhSachSinhVienRaw::create($data);
    }

    public function update(DanhSachSinhVienRaw $student, array $data): DanhSachSinhVienRaw
    {
        if (!empty($data['mat_khau_mac_dinh'])) {
            $data['mat_khau_mac_dinh'] = Hash::make($data['mat_khau_mac_dinh']);
        } else {
            unset($data['mat_khau_mac_dinh']);
        }

        $student->update($data);

        return $student;
    }

    public function delete(DanhSachSinhVienRaw $student): bool
    {
        return (bool) $student->delete();
    }

    /**
     * Nạp dữ liệu danh sách hàng loạt (Batch Import)
     */
    public function importBatch(array $records): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];
        $duplicates = [];
        $seenMssvs = [];

        foreach ($records as $index => $item) {
            $mssv = strtoupper(trim($item['mssv'] ?? ''));
            $hoTen = trim($item['ho_ten'] ?? '');
            $email = trim($item['email_sv'] ?? '');

            // 1. Kiểm tra lỗi dữ liệu thiếu bắt buộc
            if ($mssv === '') {
                $errors[] = [
                    'row' => $index + 1,
                    'mssv' => '',
                    'reason' => 'Thiếu mã số sinh viên (MSSV).',
                ];
                continue;
            }

            if ($hoTen === '') {
                $errors[] = [
                    'row' => $index + 1,
                    'mssv' => $mssv,
                    'reason' => 'Thiếu họ và tên sinh viên.',
                ];
                continue;
            }

            // 2. Kiểm tra bản ghi trùng lặp trong cùng lô nạp (batch duplicate)
            if (isset($seenMssvs[$mssv])) {
                $duplicates[] = [
                    'row' => $index + 1,
                    'mssv' => $mssv,
                    'reason' => "MSSV {$mssv} xuất hiện nhiều lần trong danh sách nạp.",
                ];
            } else {
                $seenMssvs[$mssv] = true;
            }

            // 3. Xử lý lưu hoặc cập nhật
            $existing = DanhSachSinhVienRaw::timTheoMssv($mssv);
            $rawPassword = $item['mat_khau_mac_dinh'] ?? 'Agu@' . $mssv;

            if ($existing) {
                $existing->update([
                    'ho_ten'   => $hoTen,
                    'email_sv' => $email !== '' ? $email : $existing->email_sv,
                    'ma_khoa'  => $item['ma_khoa'] ?? $existing->ma_khoa,
                ]);
                $updated++;
            } else {
                DanhSachSinhVienRaw::create([
                    'mssv'              => $mssv,
                    'ho_ten'            => $hoTen,
                    'email_sv'          => $email !== '' ? $email : strtolower($mssv) . '@student.agu.edu.vn',
                    'mat_khau_mac_dinh' => Hash::make($rawPassword),
                    'ma_khoa'           => $item['ma_khoa'] ?? null,
                    'da_kich_hoat'      => false,
                ]);
                $created++;
            }
        }

        return [
            'created'    => $created,
            'updated'    => $updated,
            'total'      => $created + $updated,
            'errors'     => $errors,
            'duplicates' => $duplicates,
        ];
    }
}
