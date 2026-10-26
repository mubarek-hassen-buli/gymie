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
        Schema::create('workout_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('member_id')
                ->constrained('members')
                ->onDelete('cascade');
            $table->foreignId('trainer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();
            $table->string('goal')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['trainer_id', 'status']);
        });

        Schema::create('workout_program_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_program_id')
                ->constrained('workout_programs')
                ->onDelete('cascade');
            $table->string('day_label');
            $table->foreignId('exercise_id')
                ->constrained('exercises')
                ->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(1);
            $table->unsignedTinyInteger('target_sets')->default(3);
            $table->string('target_reps', 50)->default('10-12');
            $table->decimal('target_rpe', 3, 1)->nullable();
            $table->unsignedSmallInteger('rest_seconds')->default(60);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_program_id', 'day_label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_program_exercises');
        Schema::dropIfExists('workout_programs');
    }
};
