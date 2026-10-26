<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_management_is_available_from_the_sidebar(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Tambah kategori')
            ->assertSee(route('categories.index'), false);
    }

    public function test_user_can_create_a_category_with_an_uppercase_code(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Alat tulis kantor',
            'code' => 'atk',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Alat tulis kantor',
            'code' => 'ATK',
        ]);
    }

    public function test_category_code_update_regenerates_codes_for_its_items(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'item_number' => '001',
            'name' => 'Kertas A4',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'rim',
        ]);

        $this->put(route('categories.update', $category), [
            'name' => 'Alat tulis dan kantor',
            'code' => 'atk2',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Alat tulis dan kantor',
            'code' => 'ATK2',
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'code' => 'ATK2-001',
            'category' => 'Alat tulis dan kantor',
        ]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'name' => 'Kertas A4',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'rim',
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
