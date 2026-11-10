<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->renameColumn('type', 'loai');
            $table->renameColumn('title', 'tieu_de');
            $table->renameColumn('message', 'noi_dung');
            $table->renameColumn('data', 'du_lieu');
            $table->renameColumn('read_at', 'da_doc_luc');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->renameColumn('loai', 'type');
            $table->renameColumn('tieu_de', 'title');
            $table->renameColumn('noi_dung', 'message');
            $table->renameColumn('du_lieu', 'data');
            $table->renameColumn('da_doc_luc', 'read_at');
        });
    }
};

