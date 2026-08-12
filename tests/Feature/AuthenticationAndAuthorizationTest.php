<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_authentication_pages_render(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
    }

    public function test_public_registration_always_creates_a_student(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Student',
            'email' => 'student@mmu.edu.my',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Administrator->value,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame(UserRole::Student, User::whereEmail('student@mmu.edu.my')->firstOrFail()->role);
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret-password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guests_are_redirected_from_management_routes(): void
    {
        $this->get('/events')->assertRedirect(route('login'));
        $this->get('/venues')->assertRedirect(route('login'));
    }

    public function test_students_cannot_access_management_routes(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/events')->assertForbidden();
        $this->get('/venues')->assertForbidden();
    }

    public function test_organizers_can_manage_events_but_not_administrator_resources(): void
    {
        $this->actingAs(User::factory()->organizer()->create());

        $this->get('/events')->assertOk();
        $this->get('/venues')->assertForbidden();
        $this->get('/schedules')->assertForbidden();
    }

    public function test_administrators_can_access_all_current_management_resources(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get('/events')->assertOk();
        $this->get('/venues')->assertOk();
        $this->get('/timeslots')->assertOk();
        $this->get('/schedules')->assertOk();
    }

    public function test_each_role_receives_its_own_dashboard(): void
    {
        foreach ([
            'student' => User::factory()->create(),
            'organizer' => User::factory()->organizer()->create(),
            'administrator' => User::factory()->administrator()->create(),
        ] as $dashboard => $user) {
            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee(ucfirst($dashboard).' dashboard');
        }
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
