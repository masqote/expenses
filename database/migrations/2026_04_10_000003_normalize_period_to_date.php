<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert YYYY-MM records to YYYY-MM-DD using the actual created_at date
        foreach (['expenses', 'incomes', 'adjustments'] as $table) {
            DB::statement("
                UPDATE `{$table}`
                SET period = DATE_FORMAT(created_at, '%Y-%m-%d')
                WHERE LENGTH(period) = 7
            ");
        }

        // Salary: use first day of the month (it's a monthly budget, no specific day needed)
        DB::statement("
            UPDATE salaries
            SET period = CONCAT(period, '-01')
            WHERE LENGTH(period) = 7
        ");
    }

    public function down(): void {}
};
