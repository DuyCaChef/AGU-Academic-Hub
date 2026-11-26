<?php

namespace Database\Seeders;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class KycSeeder extends Seeder
{
    /**
     * Seed dữ liệu mẫu cho hàng đợi duyệt hồ sơ KYC cựu sinh viên (/quan-tri/duyet-kyc).
     */
    public function run(): void
    {
        // 1. Lấy danh sách ID khoa
        $khoaMap = Khoa::pluck('ma_khoa', 'ky_hieu_khoa')->toArray();

        // 2. Danh sách hồ sơ cựu sinh viên chờ duyệt KYC mẫu
        $sampleKycList = [
            [
                'ho_ten'          => 'Đặng Quốc Bảo',
                'email'           => 'dangquocbao.alumni@gmail.com',
                'mssv'            => 'DTH185023',
                'ky_hieu_khoa'    => 'CNTT',
                'nam_tot_nghiep'  => 2022,
                'loai_minh_chung' => 'BANG_TOT_NGHIEP',
                'days_ago'        => 3,
            ],
            [
                'ho_ten'          => 'Nguyễn Thị Mai Lan',
                'email'           => 'mailan.nguyen@gmail.com',
                'mssv'            => 'DKT195045',
                'ky_hieu_khoa'    => 'KT',
                'nam_tot_nghiep'  => 2023,
                'loai_minh_chung' => 'GIAY_CHUNG_NHAN',
                'days_ago'        => 2,
            ],
            [
                'ho_ten'          => 'Lê Hoàng Nam',
                'email'           => 'hoangnam.le1999@gmail.com',
                'mssv'            => 'DSP185012',
                'ky_hieu_khoa'    => 'SP',
                'nam_tot_nghiep'  => 2022,
                'loai_minh_chung' => 'BANG_TOT_NGHIEP',
                'days_ago'        => 2,
            ],
            [
                'ho_ten'          => 'Phạm Minh Tuấn',
                'email'           => 'tuanpham.dev@gmail.com',
                'mssv'            => 'DTH175089',
                'ky_hieu_khoa'    => 'CNTT',
                'nam_tot_nghiep'  => 2021,
                'loai_minh_chung' => 'THE_SINH_VIEN',
                'days_ago'        => 1,
            ],
            [
                'ho_ten'          => 'Trần Bích Phương',
                'email'           => 'bichphuong.tran2024@gmail.com',
                'mssv'            => 'DKT205011',
                'ky_hieu_khoa'    => 'KT',
                'nam_tot_nghiep'  => 2024,
                'loai_minh_chung' => 'BANG_TOT_NGHIEP',
                'days_ago'        => 1,
            ],
            [
                'ho_ten'          => 'Vũ Đình Khang',
                'email'           => 'dinhkhang.vu@gmail.com',
                'mssv'            => 'DSP195076',
                'ky_hieu_khoa'    => 'SP',
                'nam_tot_nghiep'  => 2023,
                'loai_minh_chung' => 'GIAY_CHUNG_NHAN',
                'days_ago'        => 0,
            ],
        ];

        // Đảm bảo thư mục private/kyc trên disk local tồn tại
        if (!Storage::disk('local')->exists('private/kyc')) {
            Storage::disk('local')->makeDirectory('private/kyc');
        }

        foreach ($sampleKycList as $item) {
            $maKhoa = $khoaMap[$item['ky_hieu_khoa']] ?? null;

            // Tạo hoặc cập nhật tài khoản cựu sinh viên (trạng thái chờ xác thực)
            $user = NguoiDung::updateOrCreate(
                ['email' => $item['email']],
                [
                    'ma_khoa'            => $maKhoa,
                    'ho_ten'             => $item['ho_ten'],
                    'mssv'               => $item['mssv'],
                    'nam_tot_nghiep'     => $item['nam_tot_nghiep'],
                    'mat_khau'           => Hash::make('Agu@123456'),
                    'vai_tro'            => VaiTroNguoiDung::CUU_SINH_VIEN,
                    'trang_thai'         => TrangThaiNguoiDung::CHUA_XAC_THUC,
                    'diem_uy_tin'        => 100,
                    'xac_thuc_email_luc' => now()->subDays($item['days_ago']),
                    'created_at'         => now()->subDays($item['days_ago'])->subHours(2),
                    'updated_at'         => now()->subDays($item['days_ago']),
                ]
            );

            // Tạo file ảnh minh chứng mẫu an toàn PII trong storage private
            $fileName = sprintf('kyc_demo_%s_%s.png', strtolower($item['mssv']), date('Ymd'));
            $storagePath = 'private/kyc/' . $fileName;

            if (!Storage::disk('local')->exists($storagePath)) {
                $imageBinary = $this->generateSampleProofImage(
                    fullName: $item['ho_ten'],
                    mssv: $item['mssv'],
                    proofType: $item['loai_minh_chung'],
                    year: $item['nam_tot_nghiep']
                );
                Storage::disk('local')->put($storagePath, $imageBinary);
            }

            // Tạo hoặc cập nhật yêu cầu xác thực KYC cựu sinh viên (chờ duyệt)
            YeuCauXacThuc::updateOrCreate(
                [
                    'ma_nguoi_dung' => $user->ma_nguoi_dung,
                    'loai_xac_thuc' => LoaiXacThuc::CUU_SINH_VIEN,
                ],
                [
                    'mssv'                 => $item['mssv'],
                    'nam_tot_nghiep'       => $item['nam_tot_nghiep'],
                    'duong_dan_minh_chung' => $storagePath,
                    'loai_tep'             => 'image/png',
                    'loai_minh_chung'      => $item['loai_minh_chung'],
                    'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
                    'nguoi_duyet'          => null,
                    'thoi_gian_duyet'      => null,
                    'ly_do_tu_choi'        => null,
                    'created_at'           => now()->subDays($item['days_ago'])->subHours(1),
                    'updated_at'           => now()->subDays($item['days_ago']),
                ]
            );
        }
    }

    /**
     * Tạo ảnh minh chứng văn bằng / chứng nhận mẫu chất lượng cao bằng thư viện GD.
     */
    private function generateSampleProofImage(string $fullName, string $mssv, string $proofType, int $year): string
    {
        if (!extension_loaded('gd')) {
            // Fallback: 1x1 transparent PNG nếu máy không có GD
            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }

        $width = 900;
        $height = 620;
        $image = imagecreatetruecolor($width, $height);

        // Màu sắc
        $bg = imagecolorallocate($image, 253, 252, 248);       // Nền giấy chứng chỉ
        $bluePrimary = imagecolorallocate($image, 0, 91, 170); // Màu nhận diện AGU #005baa
        $goldBorder = imagecolorallocate($image, 212, 160, 23); // Viền vàng kim
        $textDark = imagecolorallocate($image, 30, 41, 59);     // Màu chữ đen than
        $textMuted = imagecolorallocate($image, 100, 116, 139); // Màu chữ phụ
        $sealRed = imagecolorallocate($image, 220, 38, 38);     // Màu mộc đỏ

        // Tô nền
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        // Viền trang trí bằng cấp
        imagesetthickness($image, 6);
        imagerectangle($image, 24, 24, $width - 24, $height - 24, $bluePrimary);
        imagesetthickness($image, 2);
        imagerectangle($image, 34, 34, $width - 34, $height - 34, $goldBorder);

        // Tiêu đề trường
        imagestring($image, 5, 270, 60, "DAI HOC QUOC GIA TP. HO CHI MINH", $bluePrimary);
        imagestring($image, 5, 300, 90, "TRUONG DAI HOC AN GIANG", $bluePrimary);

        $proofTitle = match ($proofType) {
            'BANG_TOT_NGHIEP'  => 'BANG TOT NGHIEP DAI HOC (BAN MAU TEST)',
            'GIAY_CHUNG_NHAN'  => 'GIAY CHUNG NHAN TOT NGHIEP TAM THOI (BAN MAU)',
            default            => 'THE SINH VIEN / GIAY TO XAC MINH (BAN MAU)',
        };
        imagestring($image, 5, 240, 150, $proofTitle, $sealRed);

        // Đường kẻ phân cách
        imageline($image, 160, 190, $width - 160, 190, $goldBorder);

        // Nội dung thông tin cựu sinh viên (không dấu để font bitmap tích hợp hiển thị sắc nét)
        $cleanName = $this->removeVietnameseAccents($fullName);
        imagestring($image, 5, 140, 240, "HO VA TEN      : " . strtoupper($cleanName), $textDark);
        imagestring($image, 5, 140, 280, "MA SO SINH VIEN: " . $mssv, $textDark);
        imagestring($image, 5, 140, 320, "NAM TOT NGHIEP : " . $year, $textDark);
        imagestring($image, 5, 140, 360, "BAC DAO TAO    : DAI HOC CHINH QUY", $textMuted);
        imagestring($image, 5, 140, 400, "TRANG THAI KYC : CHO KIEM DUYET VIEN DOI SOAT", $bluePrimary);

        // Dấu mộc & chữ ký đại diện
        imagestring($image, 4, 550, 470, "An Giang, Ngay ... Thang ... Nam ...", $textMuted);
        imagestring($image, 5, 590, 500, "HIEU TRUONG", $bluePrimary);
        imagestring($image, 5, 570, 530, "[DAU MOC DO DA XAC THUC]", $sealRed);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    /**
     * Bỏ dấu tiếng Việt phục vụ font bitmap imagestring tích hợp sẵn của PHP.
     */
    private function removeVietnameseAccents(string $str): string
    {
        $accents = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];

        foreach ($accents as $nonAccent => $accent) {
            $str = preg_replace("/($accent)/i", $nonAccent, $str);
        }

        return $str;
    }
}
