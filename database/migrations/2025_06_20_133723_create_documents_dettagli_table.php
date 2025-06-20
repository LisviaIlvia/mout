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
        Schema::create('documents_dettagli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->date('data_evasione')->nullable();
            $table->string('mod_poltrona')->nullable();
            $table->integer('quantita')->nullable();
            $table->integer('fianchi_finali')->nullable();
            $table->decimal('interasse_cm', 8, 2)->nullable();
            $table->decimal('largh_bracciolo_cm', 8, 2)->nullable();
            $table->string('rivestimento')->nullable();
            $table->boolean('ricamo_logo')->default(false);
            $table->boolean('pendenza')->default(false);
            $table->boolean('fissaggio_pavimento')->default(false);
            $table->boolean('montaggio')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_dettagli');
    }
};
