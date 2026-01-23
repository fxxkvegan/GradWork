<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tagline', 60)->default('（説明未設定）')->after('name');
        });

        DB::table('products')
            ->whereNull('tagline')
            ->orWhere('tagline', '')
            ->update([
                'tagline' => DB::raw("LEFT(COALESCE(NULLIF(description, ''), '（説明未設定）'), 60)"),
            ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tagline');
        });
    }
};
