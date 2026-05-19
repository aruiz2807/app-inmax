<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Parameter;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRouteRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_gets_forbidden_on_dashboard_route(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('user.home'));
    }

    public function test_user_profile_gets_forbidden_on_admin_prefix_route(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('policies'));

        $response->assertStatus(403);
    }

    public function test_doctor_profile_gets_forbidden_on_dashboard_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('dashboard'));

        $response->assertRedirect(route('doctor.home'));
    }

    public function test_doctor_profile_gets_forbidden_on_admin_prefix_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('policies'));

        $response->assertStatus(403);
    }

    public function test_doctor_profile_gets_forbidden_on_user_prefix_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('user.home'));

        $response->assertRedirect(route('doctor.home'));
    }

    public function test_receptionist_profile_redirects_to_its_home_when_visiting_dashboard_route(): void
    {
        $receptionist = User::factory()->create([
            'profile' => 'Receptionist',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($receptionist)->get(route('dashboard'));

        $response->assertRedirect(route('receptionist.dashboard'));
    }

    public function test_doctor_profile_can_access_doctor_prefix_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $service = Service::create([
            'name' => 'Consulta general',
            'type' => 'Event',
        ]);

        $specialty = Specialty::create([
            'name' => 'Cardiologia',
            'service_id' => $service->id,
        ]);

        Doctor::create([
            'user_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'license' => 'LIC-12345',
            'university' => 'UNAM',
            'address' => 'Av. Principal 123',
        ]);

        Parameter::create([
            'type' => 'MG',
            'key' => 'Especialidad',
            'description' => 'Especialidad medicina general',
            'value' => (string) $specialty->id,
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.home'));

        $response->assertStatus(200);
    }
}
