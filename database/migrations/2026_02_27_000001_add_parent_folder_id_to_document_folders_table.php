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
        Schema::table('document_folders', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_folder_id')->nullable()->after('id');
            $table->foreign('parent_folder_id')
                  ->references('id')
                  ->on('document_folders')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_folders', function (Blueprint $table) {
            $table->dropForeign(['parent_folder_id']);
            $table->dropColumn('parent_folder_id');
        });
    }
};
