<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'installment_plan_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('installment_plan_id')
                    ->nullable()
                    ->after('payment_type')
                    ->constrained('company_product_installment_plans')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('installment_contracts')) {
            Schema::create('installment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_product_installment_plan_id')
                ->nullable()
                ->constrained('company_product_installment_plans')
                ->nullOnDelete();
            $table->json('plan_snapshot');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('down_payment_amount', 12, 2);
            $table->unsignedTinyInteger('months');
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('outstanding_amount', 12, 2);
            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
            });
        }

        if (! Schema::hasTable('installment_schedule')) {
            Schema::create('installment_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_contract_id')->constrained('installment_contracts')->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->enum('type', ['down_payment', 'installment'])->default('installment');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'waived'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['installment_contract_id', 'installment_number'], 'installment_schedule_contract_number_uq');
            $table->index(['installment_contract_id', 'status']);
            $table->index('due_date');
            });
        }

        if (! Schema::hasTable('installment_payments')) {
            Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_contract_id')->constrained('installment_contracts')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->constrained('installment_schedule')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash');
            $table->nullableMorphs('recorded_by');
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['installment_contract_id', 'paid_at']);
            });
        }

        if (! Schema::hasTable('installment_penalties')) {
            Schema::create('installment_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_contract_id')->constrained('installment_contracts')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->constrained('installment_schedule')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['installment_schedule_id', 'applied_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_penalties');
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_schedule');
        Schema::dropIfExists('installment_contracts');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('installment_plan_id');
        });
    }
};
