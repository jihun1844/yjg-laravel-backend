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
        Schema::create('restaurant_semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('semester_meal_type_id')->constrained('semester_meal_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('payment')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_semesters', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['semester_meal_type_id']);
        });
        Schema::dropIfExists('restaurant_semesters');
    }
};
