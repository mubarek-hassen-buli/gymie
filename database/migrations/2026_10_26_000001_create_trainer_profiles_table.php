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
        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');
            $table->json('specialties')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('max_active_clients')->default(10);
            $table->enum('commission_type', ['none', 'percentage', 'fixed_per_session'])->default('none');
            $table->decimal('commission_rate', 8, 2)->nullable();
            $table->boolean('is_accepting_clients')->default(true);
            $table->timestamps();

            $table->index('is_accepting_clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_profiles');
    }
};
