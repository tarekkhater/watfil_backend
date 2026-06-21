<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by 2026_06_16_100100_create_company_likes_table (requires customers from 2026_06_16_100000).
    }

    public function down(): void
    {
        Schema::dropIfExists('company_likes');
    }
};
