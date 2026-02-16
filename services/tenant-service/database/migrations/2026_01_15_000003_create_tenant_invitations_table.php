<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('email');
            $table->string('role', 50)->default('member');
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('invited_by');
            $table->text('message')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('token', 'idx_invitations_token');
            $table->index('email', 'idx_invitations_email');
            $table->index('expires_at', 'idx_invitations_expires');
            $table->index(['tenant_id', 'email'], 'idx_invitations_tenant_email');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};
