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
        // Rimuovi campi dalla tabella documents
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['metodo_pagamento_id']);
            $table->dropForeign(['conto_bancario_id']);
            $table->dropColumn(['metodo_pagamento_id', 'conto_bancario_id']);
        });

        // Rimuovi campi dalla tabella documents_products
        Schema::table('documents_products', function (Blueprint $table) {
            $table->dropColumn(['tipo_sconto', 'sconto']);
        });

        // Rimuovi campi dalla tabella documents_altro
        Schema::table('documents_altro', function (Blueprint $table) {
            $table->dropColumn(['tipo_sconto', 'sconto', 'ricorrenza']);
        });

        // Elimina tabelle non più necessarie
        Schema::dropIfExists('documents_rate');
        Schema::dropIfExists('documents_spedizioni');
        Schema::dropIfExists('documents_trasporto');
        Schema::dropIfExists('documents_dettagli');
        Schema::dropIfExists('metodi_pagamento');
        Schema::dropIfExists('conti_bancari');
        Schema::dropIfExists('spedizioni');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea le tabelle eliminate
        Schema::create('spedizioni', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('conti_bancari', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('metodi_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('documents_dettagli', function (Blueprint $table) {
            $table->id();
            $table->date('data_evasione')->nullable();
            $table->string('mod_poltrona')->nullable();
            $table->integer('quantita')->nullable();
            $table->integer('fianchi_finali')->nullable();
            $table->integer('interasse_cm')->nullable();
            $table->integer('largh_bracciolo_cm')->nullable();
            $table->string('rivestimento')->nullable();
            $table->boolean('ricamo_logo')->default(false);
            $table->boolean('pendenza')->default(false);
            $table->boolean('fissaggio_pavimento')->default(false);
            $table->boolean('montaggio')->default(false);
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });

        Schema::create('documents_trasporto', function (Blueprint $table) {
            $table->id();
            $table->integer('colli')->nullable();
            $table->decimal('peso', 8, 2)->nullable();
            $table->string('causale')->nullable();
            $table->string('porto')->nullable();
            $table->string('a_cura')->nullable();
            $table->string('vettore')->nullable();
            $table->text('annotazioni')->nullable();
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });

        Schema::create('documents_spedizioni', function (Blueprint $table) {
            $table->id();
            $table->decimal('prezzo', 8, 2);
            $table->decimal('sconto', 8, 2);
            $table->unsignedBigInteger('aliquota_iva_id');
            $table->unsignedBigInteger('spedizione_id');
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });

        Schema::create('documents_rate', function (Blueprint $table) {
            $table->id();
            $table->date('data')->nullable();
            $table->decimal('percentuale', 8, 2);
            $table->decimal('importo', 8, 2);
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });

        // Ricrea i campi rimossi
        Schema::table('documents_altro', function (Blueprint $table) {
            $table->string('tipo_sconto');
            $table->decimal('sconto', 8, 2)->default(0.00);
            $table->string('ricorrenza');
        });

        Schema::table('documents_products', function (Blueprint $table) {
            $table->string('tipo_sconto');
            $table->decimal('sconto', 8, 2)->default(0.00);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('metodo_pagamento_id')->nullable();
            $table->unsignedBigInteger('conto_bancario_id')->nullable();
            $table->foreign('metodo_pagamento_id')->references('id')->on('metodi_pagamento');
            $table->foreign('conto_bancario_id')->references('id')->on('conti_bancari');
        });
    }
};
