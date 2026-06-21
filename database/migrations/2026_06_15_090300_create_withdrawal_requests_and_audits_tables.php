<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_reference')->nullable();
            $table->foreignId('reserved_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('release_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('withdrawal_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_request_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->nullableMorphs('actor');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_audits');
        Schema::dropIfExists('withdrawal_requests');
    }
};
