<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('supplier_product_id')->constrained('supplier_products')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['company_id', 'supplier_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_catalog');
    }
};
