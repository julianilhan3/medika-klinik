<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScheduleEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ClinicSeeder::class);

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->doctor = User::where('username', 'drbudi')->firstOrFail();

        DoctorSchedule::where('doctor_id', $this->doctor->id)->delete();
    }

    private function makeSchedule(int $day = 0, string $start = '08:00', string $end = '11:00', int $quota = 15): DoctorSchedule
    {
        return DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day' => $day,
            'start' => $start,
            'end' => $end,
            'quota' => $quota,
        ]);
    }

    public function test_admin_bisa_mengubah_jadwal_dokter(): void
    {
        $schedule = $this->makeSchedule();

        $this->actingAs($this->admin)
            ->put(route('admin.schedule.update', $schedule->id), [
                'day' => 2,
                'start' => '09:00',
                'end' => '13:00',
                'quota' => 25,
            ])
            ->assertSessionHas('status');

        $schedule->refresh();

        $this->assertSame(2, (int) $schedule->day);
        $this->assertSame('09:00', substr((string) $schedule->start, 0, 5));
        $this->assertSame('13:00', substr((string) $schedule->end, 0, 5));
        $this->assertSame(25, (int) $schedule->quota);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        $schedule = $this->makeSchedule();

        $this->actingAs($this->admin)
            ->put(route('admin.schedule.update', $schedule->id), [
                'day' => 0,
                'start' => '12:00',
                'end' => '10:00',
                'quota' => 10,
            ])
            ->assertSessionHasErrors('end');

        $this->assertSame('08:00', substr((string) $schedule->fresh()->start, 0, 5));
    }

    public function test_pindah_ke_hari_yang_sudah_punya_sesi_ditolak(): void
    {
        $senin = $this->makeSchedule(0);
        $this->makeSchedule(1, '13:00', '16:00');

        $this->actingAs($this->admin)
            ->put(route('admin.schedule.update', $senin->id), [
                'day' => 1,
                'start' => '08:00',
                'end' => '11:00',
                'quota' => 15,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, (int) $senin->fresh()->day);
    }

    public function test_admin_bisa_menghapus_jadwal(): void
    {
        $schedule = $this->makeSchedule();

        $this->actingAs($this->admin)
            ->delete(route('admin.schedule.destroy', $schedule->id))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);
    }

    public function test_dokter_tidak_bisa_mengubah_atau_menghapus_jadwal(): void
    {
        $schedule = $this->makeSchedule();

        $this->actingAs($this->doctor)
            ->put(route('admin.schedule.update', $schedule->id), [
                'day' => 3,
                'start' => '08:00',
                'end' => '11:00',
                'quota' => 15,
            ])
            ->assertRedirect();

        $this->actingAs($this->doctor)
            ->delete(route('admin.schedule.destroy', $schedule->id))
            ->assertRedirect();

        $schedule->refresh();
        $this->assertSame(0, (int) $schedule->day);
        $this->assertDatabaseHas('doctor_schedules', ['id' => $schedule->id]);
    }

    public function test_halaman_jadwal_admin_menampilkan_sesi_yang_bisa_diedit(): void
    {
        $this->withoutVite();

        $schedule = $this->makeSchedule();

        $this->actingAs($this->admin)
            ->get(route('admin.schedule', ['doctor' => 'D-002']))
            ->assertOk()
            ->assertSee('edit-session', false)
            ->assertSee('\\u0022id\\u0022:'.$schedule->id, false);
    }
}
