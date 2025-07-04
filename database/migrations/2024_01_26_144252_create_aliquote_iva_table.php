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
		Schema::create('aliquote_iva', function (Blueprint $table) {
			$table->id();
			$table->decimal('aliquota', 8, 2);
			$table->string('nome')->nullable();
			$table->text('descrizione')->nullable();
			$table->boolean('predefinita');
			$table->timestamps();
			$table->softDeletes();
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aliquote_iva');
    }
};
