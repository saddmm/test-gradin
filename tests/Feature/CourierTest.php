<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_couriers_with_default_per_page_and_sort_by_name_asc(): void
    {
        Courier::factory()->create(['name' => 'Charlie Courier']);
        Courier::factory()->create(['name' => 'Alice Courier']);
        Courier::factory()->create(['name' => 'Bob Courier']);

        $response = $this->getJson('/api/couriers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'email',
                        'phone',
                        'vehicle_type',
                        'license_plate',
                        'level',
                        'is_active',
                        'registered_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertSame(['Alice Courier', 'Bob Courier', 'Charlie Courier'], $names);
        $this->assertSame(15, $response->json('meta.per_page'));
    }

    public function test_index_can_override_sorting_by_registered_at_desc(): void
    {
        Courier::factory()->create([
            'name' => 'Oldest',
            'registered_at' => now()->subDays(10),
        ]);
        Courier::factory()->create([
            'name' => 'Newest',
            'registered_at' => now(),
        ]);
        Courier::factory()->create([
            'name' => 'Middle',
            'registered_at' => now()->subDays(3),
        ]);

        $response = $this->getJson('/api/couriers?sort_by=registered_at&sort_dir=desc');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertSame(['Newest', 'Middle', 'Oldest'], $names);
    }

    public function test_index_multi_word_tokenized_search_matches_and_excludes_non_matches(): void
    {
        // Should match "budi agung"
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Agung Budi Prasetyo']);

        // Should not match (only has budi or agung, or neither)
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Agung Laksono']);
        Courier::factory()->create(['name' => 'Siti Nurhaliza']);

        $response = $this->getJson('/api/couriers?search=budi+agung');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $matchedNames = collect($data)->pluck('name')->all();
        $this->assertContains('Budiono Hadi Agung', $matchedNames);
        $this->assertContains('Agung Budi Prasetyo', $matchedNames);
        $this->assertNotContains('Budi Santoso', $matchedNames);
        $this->assertNotContains('Agung Laksono', $matchedNames);
    }

    public function test_index_filters_by_comma_separated_levels(): void
    {
        Courier::factory()->create(['name' => 'Level 1 Courier', 'level' => 1]);
        Courier::factory()->create(['name' => 'Level 2 Courier', 'level' => 2]);
        Courier::factory()->create(['name' => 'Level 3 Courier', 'level' => 3]);
        Courier::factory()->create(['name' => 'Level 4 Courier', 'level' => 4]);
        Courier::factory()->create(['name' => 'Level 5 Courier', 'level' => 5]);

        $response = $this->getJson('/api/couriers?level=2,3');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $levels = collect($data)->pluck('level')->all();
        $this->assertEqualsCanonicalizing([2, 3], $levels);
    }

    public function test_show_returns_single_courier(): void
    {
        $courier = Courier::factory()->create([
            'code' => 'CR-0001',
            'name' => 'John Doe',
        ]);

        $response = $this->getJson("/api/couriers/{$courier->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $courier->id,
                    'code' => 'CR-0001',
                    'name' => 'John Doe',
                ],
            ]);
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/couriers/99999');

        $response->assertNotFound();
    }

    public function test_store_saves_valid_courier_to_database(): void
    {
        $payload = [
            'code' => 'CR-1001',
            'name' => 'Ahmad Subagyo',
            'email' => 'ahmad.subagyo@example.com',
            'phone' => '+6281234567890',
            'vehicle_type' => 'motorcycle',
            'license_plate' => 'B 1234 ABC',
            'level' => 3,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/couriers', $payload);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'code' => 'CR-1001',
                    'name' => 'Ahmad Subagyo',
                    'email' => 'ahmad.subagyo@example.com',
                    'phone' => '+6281234567890',
                    'vehicle_type' => 'motorcycle',
                    'license_plate' => 'B 1234 ABC',
                    'level' => 3,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('couriers', [
            'code' => 'CR-1001',
            'email' => 'ahmad.subagyo@example.com',
        ]);
    }

    public function test_store_rejects_invalid_data_such_as_invalid_level_or_missing_fields(): void
    {
        // Missing required fields and level = 6 (out of range 1-5)
        $invalidPayload = [
            'code' => 'CR-1002',
            'level' => 6,
            'vehicle_type' => 'spaceship', // invalid enum
        ];

        $response = $this->postJson('/api/couriers', $invalidPayload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'license_plate', 'level', 'vehicle_type']);
    }

    public function test_update_modifies_courier_in_database(): void
    {
        $courier = Courier::factory()->create([
            'code' => 'CR-0010',
            'name' => 'Old Name',
            'level' => 1,
        ]);

        $response = $this->putJson("/api/couriers/{$courier->id}", [
            'name' => 'Updated Name',
            'level' => 4,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $courier->id,
                    'name' => 'Updated Name',
                    'level' => 4,
                ],
            ]);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Updated Name',
            'level' => 4,
        ]);
    }

    public function test_update_rejects_duplicate_email_belonging_to_another_courier(): void
    {
        Courier::factory()->create(['email' => 'existing@example.com']);
        $courierToUpdate = Courier::factory()->create(['email' => 'myemail@example.com']);

        $response = $this->putJson("/api/couriers/{$courierToUpdate->id}", [
            'email' => 'existing@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_destroy_deletes_courier_from_database(): void
    {
        $courier = Courier::factory()->create([
            'code' => 'CR-9999',
        ]);

        $this->assertDatabaseHas('couriers', ['id' => $courier->id]);

        $response = $this->deleteJson("/api/couriers/{$courier->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Courier deleted successfully']);

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
    }
}
