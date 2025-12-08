<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('has_attachments');
            $table->timestamp('deleted_at')->nullable()->after('is_deleted');
            $table->timestamp('edited_at')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['edited_at', 'deleted_at', 'is_deleted']);
        });
    }
};
