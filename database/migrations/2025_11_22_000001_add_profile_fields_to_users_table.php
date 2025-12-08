<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name', 50)->nullable()->after('name');
            }

            if (!Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 160)->nullable()->after('header_url');
            }

            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location', 50)->nullable()->after('bio');
            }

            if (!Schema::hasColumn('users', 'website')) {
                $table->string('website')->nullable()->after('location');
            }

            if (!Schema::hasColumn('users', 'birthday')) {
                $table->date('birthday')->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'display_name',
                'bio',
                'location',
                'website',
                'birthday',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
