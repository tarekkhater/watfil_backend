<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_products', function (Blueprint $table) {
            $table->decimal('cash_price', 10, 2)->default(0)->after('image');
        });

        DB::statement('UPDATE company_products SET cash_price = price');

        Schema::table('company_products', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('company_products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('image');
        });

        DB::statement('UPDATE company_products SET price = cash_price');

        Schema::table('company_products', function (Blueprint $table) {
            $table->dropColumn('cash_price');
        });
    }
};
