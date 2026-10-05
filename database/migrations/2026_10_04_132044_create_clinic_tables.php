<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poli', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->enum('role', ['admin', 'doctor', 'pharmacist'])->nullable();
            $table->string('phone')->nullable();
            $table->string('sip')->nullable();
            $table->foreignId('poli_id')
                ->nullable()
                ->constrained('poli')
                ->nullOnDelete();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('rm')->unique();
            $table->string('nik')->unique();
            $table->string('name');
            $table->string('gender')->nullable();
            $table->date('birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('blood_type')->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relation')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('insurance_number')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->time('start');
            $table->time('end');
            $table->string('room')->nullable();
            $table->unsignedInteger('quota')->default(20);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();
            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('poli_id')
                ->constrained('poli')
                ->cascadeOnDelete();
            $table->date('date');
            $table->time('time');
            $table->text('complaint')->nullable();
            $table->unsignedInteger('queue_no')->nullable();
            $table->enum('type', ['online', 'walkin'])->default('online');
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'checked_in',
                'done',
                'cancelled',
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                ->unique()
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->foreignId('doctor_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('complaint')->nullable();
            $table->string('bp')->nullable();
            $table->decimal('temp', 5, 2)->nullable();
            $table->unsignedInteger('pulse')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('note')->nullable();
            $table->text('anamnesis')->nullable();
            $table->string('icd')->nullable();
            $table->text('therapy')->nullable();
            $table->text('education')->nullable();
            $table->timestamps();
        });

        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->date('expiry')->nullable();
            $table->string('class')->nullable();
            $table->string('form')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('examination_id')
                ->constrained('examinations')
                ->cascadeOnDelete();
            $table->enum('status', [
                'Menunggu',
                'Diproses',
                'Siap',
                'Diserahkan',
            ])->default('Menunggu');
            $table->string('pharmacy_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('handed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('handed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')
                ->constrained('prescriptions')
                ->cascadeOnDelete();
            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->string('dose')->nullable();
            $table->string('rule')->nullable();
            $table->string('form')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->cascadeOnDelete();
            $table->integer('qty');
            $table->date('expiry')->nullable();
            $table->string('batch')->nullable();
            $table->string('type');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('examinations');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('doctor_schedules');
        Schema::dropIfExists('patients');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['poli_id']);
            $table->dropColumn([
                'username',
                'role',
                'phone',
                'sip',
                'poli_id',
            ]);
        });

        Schema::dropIfExists('poli');
    }
};