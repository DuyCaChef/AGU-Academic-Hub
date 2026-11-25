<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tai_lieu', function (Blueprint $table) {
            if (! Schema::hasColumn('tai_lieu', 'mo_ta')) {
                $table->text('mo_ta')->nullable()->after('tieu_de');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tai_lieu', function (Blueprint $table) {
            if (Schema::hasColumn('tai_lieu', 'mo_ta')) {
                $table->dropColumn('mo_ta');
            }
        });
    }
};
