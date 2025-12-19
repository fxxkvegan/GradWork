<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'file_status')) {
                $table->string('file_status', 20)
                    ->default('none')
                    ->after('image_url');
            }
        });

        Schema::create('product_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 10)->default('file');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime')->nullable();
            $table->boolean('is_previewable')->default(false);
            $table->longText('preview_text')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_files');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'file_status')) {
                $table->dropColumn('file_status');
            }
        });
    }
};
