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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('product_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('label')->after('product_id');
            $table->decimal('price', 10, 2)->after('label');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('price');
            $table->boolean('is_default')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'label', 'price', 'sort_order', 'is_default']);
        });
    }
};
