<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nguoi_dung', function (Blueprint $table): void {
            $table->enum('cap_quan_tri', ['THUONG', 'TOI_CAO'])
                ->nullable()
                ->after('vai_tro')
                ->comment('Cấp quản trị, chỉ dùng cho QUAN_TRI_VIEN');
            $table->index(['vai_tro', 'cap_quan_tri']);
        });

        Schema::table('lich_su_phan_quyen_nguoi_dung', function (Blueprint $table): void {
            $table->string('cap_quan_tri_cu', 20)->nullable()->after('vai_tro_moi');
            $table->string('cap_quan_tri_moi', 20)->nullable()->after('cap_quan_tri_cu');
        });

        DB::table('nguoi_dung')
            ->where('vai_tro', 'QUAN_TRI_VIEN')
            ->update(['cap_quan_tri' => 'THUONG']);

        DB::table('nguoi_dung')
            ->where('email', 'admin@agu.edu.vn')
            ->where('vai_tro', 'QUAN_TRI_VIEN')
            ->update([
                'cap_quan_tri' => 'TOI_CAO',
                'ma_khoa' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('lich_su_phan_quyen_nguoi_dung', function (Blueprint $table): void {
            $table->dropColumn(['cap_quan_tri_cu', 'cap_quan_tri_moi']);
        });

        Schema::table('nguoi_dung', function (Blueprint $table): void {
            $table->dropIndex(['vai_tro', 'cap_quan_tri']);
            $table->dropColumn('cap_quan_tri');
        });
    }
};
