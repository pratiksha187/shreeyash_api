<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('category')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'complaints_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['status', 'created_at'], 'complaints_status_created_index');
            $table->index('created_at', 'complaints_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
