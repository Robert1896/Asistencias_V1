<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dias_feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre', 120);
            $table->boolean('recurrente')->default(false)->comment('Se repite cada año');
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_feriados');
    }
};
