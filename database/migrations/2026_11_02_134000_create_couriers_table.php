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
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name')->index();
            $table->string('email')->unique();
            $table->string('phone', 20)->unique();
            $table->enum('vehicle_type', ['motorcycle', 'car', 'van', 'truck']);
            $table->string('license_plate', 15);
            $table->unsignedTinyInteger('level')->default(1)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamp('registered_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
