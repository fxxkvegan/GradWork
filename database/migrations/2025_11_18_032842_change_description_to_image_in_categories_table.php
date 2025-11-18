<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // description カラムを削除
            $table->dropColumn('description');
            
            // image カラムを追加
            $table->string('image')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // image カラムを削除
            $table->dropColumn('image');
            
            // description カラムを復元
            $table->text('description')->nullable()->after('name');
        });
    }
};
