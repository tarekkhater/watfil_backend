<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_product_installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_product_id')->constrained('supplier_products')->cascadeOnDelete();
            $table->unsignedTinyInteger('months');
            $table->decimal('down_payment', 10, 2);
            $table->decimal('installment_amount', 10, 2);
            $table->timestamps();

            $table->unique(['supplier_product_id', 'months']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product_installment_plans');
    }
};
