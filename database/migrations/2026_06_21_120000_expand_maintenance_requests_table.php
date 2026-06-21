<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('company_id');
            $table->string('phone', 20)->nullable()->after('full_name');
            $table->foreignId('governorate_id')->nullable()->after('phone')->constrained('governorates')->nullOnDelete();
            $table->string('city')->nullable()->after('governorate_id');
            $table->string('area')->nullable()->after('city');
            $table->text('address_details')->nullable()->after('area');
            $table->text('device_details')->nullable()->after('address_details');
            $table->string('purification_system', 50)->nullable()->after('device_details');
            $table->unsignedTinyInteger('stages_count')->nullable()->after('purification_system');
            $table->json('last_stage_change_dates')->nullable()->after('stages_count');
            $table->string('primary_problem_type', 50)->nullable()->after('last_stage_change_dates');
            $table->string('malfunction_type', 50)->nullable()->after('primary_problem_type');
            $table->text('notes')->nullable()->after('malfunction_type');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('governorate_id');
            $table->dropColumn([
                'full_name',
                'phone',
                'city',
                'area',
                'address_details',
                'device_details',
                'purification_system',
                'stages_count',
                'last_stage_change_dates',
                'primary_problem_type',
                'malfunction_type',
                'notes',
            ]);
        });
    }
};
