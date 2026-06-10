<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('company_product_installment_plans');

        Schema::create('company_product_installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_product_id')->constrained('company_products')->cascadeOnDelete();
            $table->unsignedTinyInteger('months');
            $table->decimal('down_payment', 10, 2);
            $table->decimal('installment_amount', 10, 2);
            $table->timestamps();

            $table->unique(['company_product_id', 'months'], 'cp_install_plans_product_months_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_product_installment_plans');
    }
};
