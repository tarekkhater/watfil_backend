<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45)->unique();
            $table->string('name_ar', 45);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 400);
            $table->foreignId('parent_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_type_id')->nullable()->constrained('product_types')->nullOnDelete();
            $table->unsignedTinyInteger('number_of_stages')->nullable();
            $table->timestamps();

            $table->index(['product_type_id', 'parent_category_id']);
            $table->index('number_of_stages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('product_types');
    }
};
