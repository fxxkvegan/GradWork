<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('download_count', 'access_count');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('google_play_url')->nullable()->after('access_count');
            $table->string('app_store_url')->nullable()->after('google_play_url');
            $table->string('web_app_url')->nullable()->after('app_store_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['google_play_url', 'app_store_url', 'web_app_url']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('access_count', 'download_count');
        });
    }
};
