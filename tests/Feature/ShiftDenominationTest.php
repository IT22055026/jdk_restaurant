<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShiftDenominationTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_shift_computes_actual_total_from_denominations()
    {
        $user = User::factory()->create();

        $start = $this->actingAs($user)->postJson(route('shifts.start'), ['opening_balance' => 1000]);
        $start->assertStatus(200);
        $shiftId = $start->json('shift_id');

        $response = $this->actingAs($user)->postJson(route('shifts.close'), [
            'shift_id' => $shiftId,
            'denominations' => [
                ['denomination' => 5000, 'quantity' => 1],
                ['denomination' => 1000, 'quantity' => 2],
                ['denomination' => 500, 'quantity' => 0],
                ['denomination' => 100, 'quantity' => 0],
                ['denomination' => 50, 'quantity' => 0],
                ['denomination' => 20, 'quantity' => 0],
            ],
            'notes' => 'End of day count',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // 5000*1 + 1000*2 = 7000
        $this->assertDatabaseHas('shifts', [
            'id' => $shiftId,
            'actual_total' => 7000,
        ]);

        $this->assertEquals(2, DB::table('shift_cash_denominations')
            ->where('shift_id', $shiftId)
            ->where('quantity', '>', 0)
            ->count());

        $this->assertDatabaseHas('shift_cash_denominations', [
            'shift_id' => $shiftId,
            'denomination' => 5000,
            'quantity' => 1,
            'subtotal' => 5000,
        ]);
    }

    public function test_close_shift_rejects_invalid_denomination_value()
    {
        $user = User::factory()->create();
        $start = $this->actingAs($user)->postJson(route('shifts.start'), []);
        $shiftId = $start->json('shift_id');

        $response = $this->actingAs($user)->postJson(route('shifts.close'), [
            'shift_id' => $shiftId,
            'denominations' => [
                ['denomination' => 25, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }
}
