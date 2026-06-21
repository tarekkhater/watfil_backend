<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('company_id')->constrained('categories')->nullOnDelete();
        });

        Schema::table('supplier_products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('supplier_id')->constrained('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('supplier_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
