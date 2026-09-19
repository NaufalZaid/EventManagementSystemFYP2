<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Society;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_promote_a_registered_student_and_assign_a_society(): void
    {
        $administrator = User::factory()->administrator()->create();
        $student = User::factory()->create();
        $society = Society::factory()->create();

        $this->actingAs($administrator)->get(route('users.index'))->assertOk()->assertSee($student->email);
        $this->get(route('users.edit', $student))->assertOk()->assertSee($society->name);
        $this->put(route('users.update', $student), [
            'role' => UserRole::Organizer->value,
            'society_id' => $society->id,
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame(UserRole::Organizer, $student->role);
        $this->assertSame($society->id, $student->society_id);
    }

    public function test_organizer_role_requires_an_active_society(): void
    {
        $administrator = User::factory()->administrator()->create();
        $student = User::factory()->create();
        $inactiveSociety = Society::factory()->create(['is_active' => false]);

        $this->actingAs($administrator)->put(route('users.update', $student), [
            'role' => UserRole::Organizer->value,
            'society_id' => $inactiveSociety->id,
        ])->assertSessionHasErrors('society_id');

        $this->assertSame(UserRole::Student, $student->fresh()->role);
    }

    public function test_non_administrators_cannot_manage_users(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->get(route('users.index'))->assertForbidden();
    }

    public function test_administrator_cannot_remove_their_own_admin_access(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->put(route('users.update', $administrator), [
            'role' => UserRole::Student->value,
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Administrator, $administrator->fresh()->role);
    }

    public function test_changing_an_organizer_to_student_clears_their_society(): void
    {
        $administrator = User::factory()->administrator()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($administrator)->put(route('users.update', $organizer), [
            'role' => UserRole::Student->value,
            'society_id' => $organizer->society_id,
        ])->assertRedirect(route('users.index'));

        $organizer->refresh();
        $this->assertSame(UserRole::Student, $organizer->role);
        $this->assertNull($organizer->society_id);
    }
}
