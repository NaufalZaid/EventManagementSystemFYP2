<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhaseZeroUiTest extends TestCase
{
    use RefreshDatabase;

    public static function prototypePages(): array
    {
        return [
            'event list' => ['/events'],
            'event form' => ['/events/create'],
            'venue list' => ['/venues'],
            'venue form' => ['/venues/create'],
            'timeslot list' => ['/timeslots'],
            'timeslot form' => ['/timeslots/create'],
            'schedule list' => ['/schedules'],
            'schedule form' => ['/schedules/create'],
        ];
    }

    #[DataProvider('prototypePages')]
    public function test_prototype_pages_render_successfully(string $uri): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get($uri)->assertOk();
    }
}
