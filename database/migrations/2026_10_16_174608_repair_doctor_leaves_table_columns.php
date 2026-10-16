<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_leaves', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_leaves', 'doctor_id')) {
                $table->foreignId('doctor_id')
                    ->nullable()
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('doctor_leaves', 'date')) {
                $table->date('date')->nullable();
            }

            if (! Schema::hasColumn('doctor_leaves', 'scope')) {
                $table->string('scope', 20)->default('full');
            }

            if (! Schema::hasColumn('doctor_leaves', 'reason')) {
                $table->string('reason')->nullable();
            }
        });

if (
    Schema::hasColumn('doctor_leaves', 'doctor_id') &&
    Schema::hasColumn('doctor_leaves', 'date') &&
    ! Schema::hasIndex(
        'doctor_leaves',
        'doctor_leaves_doctor_date_unique',
        'unique'
    )
) {
    Schema::table('doctor_leaves', function (Blueprint $table) {
        $table->unique(
            ['doctor_id', 'date'],
            'doctor_leaves_doctor_date_unique'
        );
    });
}
    }

    public function down(): void
    {
        Schema::table('doctor_leaves', function (Blueprint $table) {
            $table->dropUnique('doctor_leaves_doctor_date_unique');
            $table->dropConstrainedForeignId('doctor_id');
            $table->dropColumn(['date', 'scope', 'reason']);
        });
    }
};