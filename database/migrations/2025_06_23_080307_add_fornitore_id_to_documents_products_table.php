<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Creo la colonna fornitore_id nella tabella documents_products
    public function up(): void
    {
        Schema::table('documents_products', function (Blueprint $table) {
            $table->foreignId('fornitore_id')->nullable()->constrained('entities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents_products', function (Blueprint $table) {
            $table->dropForeign(['fornitore_id']);
            $table->dropColumn('fornitore_id');
        });
    }
};
