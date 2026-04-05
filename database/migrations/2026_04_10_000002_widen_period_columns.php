<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen period columns from char(7) to varchar(10) to support YYYY-MM-DD
        foreach (['expenses', 'incomes', 'salaries', 'adjustments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('period', 10)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['expenses', 'incomes', 'salaries', 'adjustments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->char('period', 7)->change();
            });
        }
    }
};
