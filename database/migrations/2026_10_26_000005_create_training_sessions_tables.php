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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();
            $table->foreignId('member_id')
                ->constrained('members')
                ->onDelete('cascade');
            $table->foreignId('trainer_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('status')->default('scheduled');
            $table->text('trainer_notes')->nullable();
            $table->text('client_feedback')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'scheduled_at']);
            $table->index(['member_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('session_workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')
                ->constrained('training_sessions')
                ->onDelete('cascade');
            $table->foreignId('exercise_id')
                ->constrained('exercises')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('set_number')->default(1);
            $table->decimal('weight_kg', 6, 2)->default(0);
            $table->unsignedSmallInteger('reps_completed')->default(0);
            $table->decimal('rpe', 3, 1)->nullable();
            $table->boolean('is_pr')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['training_session_id', 'exercise_id']);
            $table->index(['exercise_id', 'weight_kg']);
            $table->index('is_pr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_workout_logs');
        Schema::dropIfExists('training_sessions');
    }
};
