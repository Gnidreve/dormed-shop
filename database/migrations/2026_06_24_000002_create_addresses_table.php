<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('both'); // shipping|billing|both
            $table->string('company', 255)->nullable();
            $table->string('salutation', 10)->nullable(); // Herr|Frau
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('street', 255);
            $table->string('house_number', 20);
            $table->string('address_line2', 255)->nullable();
            $table->string('zip', 20);
            $table->string('city', 255);
            $table->string('country', 2)->default('DE');
            $table->string('phone', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'type']);
            $table->index('zip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
