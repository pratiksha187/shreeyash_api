<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 20)->nullable()->after('mobile');
            $table->string('marital_status', 20)->nullable()->after('gender');
            $table->date('date_of_birth')->nullable()->after('marital_status');
            $table->date('join_date')->nullable()->after('date_of_birth');
            $table->date('confirmation_date')->nullable()->after('join_date');
            $table->unsignedInteger('probation_months')->nullable()->after('confirmation_date');
            $table->string('aadhaar_number', 20)->nullable()->after('probation_months');
            $table->decimal('hours_per_day', 5, 2)->nullable()->after('aadhaar_number');
            $table->unsignedTinyInteger('days_per_week')->nullable()->after('hours_per_day');
            $table->decimal('salary', 12, 2)->nullable()->after('days_per_week');
            $table->decimal('insurance', 12, 2)->nullable()->after('salary');
            $table->decimal('pt', 12, 2)->nullable()->after('insurance');
            $table->decimal('advance', 12, 2)->nullable()->after('pt');
            $table->decimal('pf', 12, 2)->nullable()->after('advance');
            $table->string('designation')->nullable()->after('pf');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'marital_status',
                'date_of_birth',
                'join_date',
                'confirmation_date',
                'probation_months',
                'aadhaar_number',
                'hours_per_day',
                'days_per_week',
                'salary',
                'insurance',
                'pt',
                'advance',
                'pf',
                'designation',
            ]);
        });
    }
};
