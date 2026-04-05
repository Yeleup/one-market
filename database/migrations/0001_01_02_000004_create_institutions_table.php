<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->integer('max_weight_grams');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('institution_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_translations');
        Schema::dropIfExists('institutions');
    }
};
