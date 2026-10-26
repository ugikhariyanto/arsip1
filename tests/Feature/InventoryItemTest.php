<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_inventory_summary(): void
    {
        InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'name' => 'Kertas A4',
            'category' => 'ATK',
            'quantity' => 12,
            'unit' => 'rim',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Kertas A4')
            ->assertSeeText('12');
    }

    public function test_user_can_create_a_consumable_item(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'category_id' => $category->id,
            'item_number' => '001',
            'name' => 'Kertas A4',
            'quantity' => 5,
            'unit' => 'rim',
            'location' => 'Gudang umum',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]));

        $this->assertDatabaseHas('inventory_items', [
            'code' => 'ATK-001',
            'category_id' => $category->id,
            'item_number' => '001',
            'name' => 'Kertas A4',
            'quantity' => 5,
            'type' => InventoryItem::TYPE_CONSUMABLE,
        ]);
    }

    public function test_assets_require_at_least_one_unit(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);

        $this->from(route('inventory.create'))
            ->post(route('inventory.store'), [
                'type' => InventoryItem::TYPE_ASSET,
                'category_id' => $category->id,
                'item_number' => '001',
                'name' => 'Komputer',
                'quantity' => 0,
                'unit' => 'unit',
            ])
            ->assertRedirect(route('inventory.create'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_asset_price_is_saved_as_rupiah_after_removing_thousand_separators(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_ASSET,
            'category_id' => $category->id,
            'item_number' => '010',
            'name' => 'Komputer',
            'quantity' => 1,
            'unit' => 'unit',
            'price' => '1.000.000',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]));

        $item = InventoryItem::query()->where('code', 'ELK-010')->firstOrFail();
        $this->assertSame(1000000, $item->price);

        $this->get(route('inventory.edit', $item))
            ->assertOk()
            ->assertSee('value="1.000.000"', false);
        $this->get(route('inventory.show', $item))
            ->assertOk()
            ->assertSeeText('Rp 1.000.000');
    }

    public function test_price_is_not_saved_for_consumable_items(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'category_id' => $category->id,
            'item_number' => '013',
            'name' => 'Kertas A4',
            'quantity' => 5,
            'unit' => 'rim',
            'price' => '1.000',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]));

        $this->assertDatabaseHas('inventory_items', [
            'code' => 'ATK-013',
            'price' => null,
        ]);
    }

    public function test_asset_price_rejects_invalid_thousand_grouping(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);

        $this->from(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->post(route('inventory.store'), [
                'type' => InventoryItem::TYPE_ASSET,
                'category_id' => $category->id,
                'item_number' => '014',
                'name' => 'Komputer',
                'quantity' => 1,
                'unit' => 'unit',
                'price' => '10.00',
            ])
            ->assertRedirect(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->assertSessionHasErrors('price');
    }

    public function test_inventory_can_be_searched_and_filtered_by_type(): void
    {
        InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'name' => 'Kertas A4',
            'category' => 'ATK',
            'quantity' => 5,
            'unit' => 'rim',
        ]);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'AST-001',
            'name' => 'Komputer',
            'category' => 'Elektronik',
            'quantity' => 1,
            'unit' => 'unit',
        ]);

        $this->get(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET, 'search' => 'Kom']))
            ->assertOk()
            ->assertSeeText('Komputer')
            ->assertDontSeeText('Kertas A4');
    }

    public function test_sidebar_has_separate_links_for_each_inventory_type(): void
    {
        $this->get(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]))
            ->assertOk()
            ->assertSeeText('Barang habis pakai')
            ->assertSee(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]), false)
            ->assertSee(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]), false);
    }

    public function test_inventory_list_renders_actions_as_buttons(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'item_number' => '001',
            'name' => 'Kertas A4',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'rim',
        ]);

        $this->get(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]))
            ->assertOk()
            ->assertSee('class="action-button action-view"', false)
            ->assertSee('class="action-button action-edit"', false)
            ->assertSee('class="action-button action-delete"', false);
    }

    public function test_asset_menu_opens_an_asset_form_with_asset_defaults(): void
    {
        Category::create(['name' => 'Elektronik', 'code' => 'ELK']);

        $this->get(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->assertOk()
            ->assertSee('value="asset" selected', false)
            ->assertSee('name="quantity" min="1" step="1" value="1" required', false)
            ->assertSee('name="category_id"', false)
            ->assertSee('name="item_number"', false);
    }

    public function test_item_can_be_updated_and_deleted(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'item_number' => '001',
            'name' => 'Kertas A4',
            'category' => 'ATK',
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'rim',
        ]);

        $this->put(route('inventory.update', $item), [
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'category_id' => $category->id,
            'item_number' => '001',
            'name' => 'Kertas A4 Baru',
            'quantity' => 8,
            'unit' => 'rim',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]));

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'name' => 'Kertas A4 Baru', 'quantity' => 8]);

        $this->delete(route('inventory.destroy', $item))
            ->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]));

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_category_code_and_item_number_generate_the_full_item_code(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'atk']);

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'category_id' => $category->id,
            'item_number' => '007',
            'name' => 'Pulpen',
            'quantity' => 4,
            'unit' => 'pcs',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]));

        $this->assertDatabaseHas('inventory_items', [
            'code' => 'ATK-007',
            'item_number' => '007',
            'category' => 'Alat tulis kantor',
        ]);
    }

    public function test_category_and_item_number_must_be_unique_together(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-001',
            'item_number' => '001',
            'name' => 'Kertas A4',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'rim',
        ]);

        $this->from(route('inventory.create'))
            ->post(route('inventory.store'), [
                'type' => InventoryItem::TYPE_CONSUMABLE,
                'category_id' => $category->id,
                'item_number' => '001',
                'name' => 'Pulpen',
                'quantity' => 4,
                'unit' => 'pcs',
            ])
            ->assertRedirect(route('inventory.create'))
            ->assertSessionHasErrors('item_number');
    }

    public function test_item_photo_can_be_previewed_on_form_and_uploaded(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        $photo = $this->fakePng('kertas.png');

        $this->get(route('inventory.create'))
            ->assertOk()
            ->assertSee('data-photo-input', false)
            ->assertSee('data-photo-preview', false);

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'category_id' => $category->id,
            'item_number' => '011',
            'name' => 'Kertas A4',
            'quantity' => 5,
            'unit' => 'rim',
            'photo' => $photo,
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]));

        $item = InventoryItem::query()->where('code', 'ATK-011')->firstOrFail();

        $this->assertNotNull($item->photo_path);
        Storage::disk('public')->assertExists($item->photo_path);
        $this->get(route('inventory.edit', $item))
            ->assertOk()
            ->assertSee('storage/'.$item->photo_path, false);
        $this->get(route('inventory.index', ['type' => InventoryItem::TYPE_CONSUMABLE]))
            ->assertOk()
            ->assertSee('storage/'.$item->photo_path, false);
    }

    public function test_item_detail_shows_large_photo_and_inventory_information(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);
        $photoPath = $this->fakePng('printer.png')->store('inventory', 'public');
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'ELK-001',
            'item_number' => '001',
            'name' => 'Printer',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
            'location' => 'Ruang administrasi',
            'condition' => 'Baik',
            'price' => 1000000,
            'notes' => 'Printer inventaris',
            'photo_path' => $photoPath,
        ]);

        $this->get(route('inventory.show', $item))
            ->assertOk()
            ->assertSee('item-photo-large', false)
            ->assertSee('storage/'.$photoPath, false)
            ->assertSeeText('Printer')
            ->assertSeeText('ELK-001')
            ->assertSeeText('Nomor barang')
            ->assertSeeText('Kode kategori')
            ->assertSeeText('Ruang administrasi')
            ->assertSeeText('Baik')
            ->assertSeeText('Rp 1.000.000')
            ->assertSee('data-price-detail', false)
            ->assertSee('class="action-button action-edit"', false)
            ->assertSeeText('Ubah data');
    }

    public function test_asset_without_a_price_shows_an_explicit_empty_value_on_detail(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'ELK-020',
            'item_number' => '020',
            'name' => 'Printer',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
        ]);

        $this->get(route('inventory.show', $item))
            ->assertOk()
            ->assertSeeText('Harga barang')
            ->assertSeeText('Belum diisi')
            ->assertSee('data-price-detail', false);
    }

    public function test_consumable_detail_shows_all_applicable_input_data(): void
    {
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_CONSUMABLE,
            'code' => 'ATK-021',
            'item_number' => '021',
            'name' => 'Kertas A4',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'rim',
            'location' => 'Gudang umum',
            'notes' => 'Disimpan di rak atas',
        ]);

        $this->get(route('inventory.show', $item))
            ->assertOk()
            ->assertSeeText('Barang habis pakai')
            ->assertSeeText('Kode lengkap')
            ->assertSeeText('ATK-021')
            ->assertSeeText('Nomor barang')
            ->assertSeeText('021')
            ->assertSeeText('Kode kategori')
            ->assertSeeText('ATK')
            ->assertSeeText('Jumlah')
            ->assertSeeText('5')
            ->assertSeeText('Satuan')
            ->assertSeeText('rim')
            ->assertSeeText('Gudang umum')
            ->assertSeeText('Disimpan di rak atas');
    }

    public function test_item_detail_displays_a_placeholder_when_no_photo_exists(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'ELK-002',
            'item_number' => '002',
            'name' => 'Scanner',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
        ]);

        $this->get(route('inventory.show', $item))
            ->assertOk()
            ->assertSeeText('Belum ada foto')
            ->assertSeeText('Tambahkan foto melalui menu ubah barang.');
    }

    public function test_item_photo_can_be_replaced_removed_and_is_deleted_with_item(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);
        $oldPhotoPath = $this->fakePng('old.png')->store('inventory', 'public');
        $item = InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'ELK-001',
            'item_number' => '001',
            'name' => 'Printer',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
            'photo_path' => $oldPhotoPath,
        ]);

        $this->put(route('inventory.update', $item), [
            'type' => InventoryItem::TYPE_ASSET,
            'category_id' => $category->id,
            'item_number' => '001',
            'name' => 'Printer',
            'quantity' => 1,
            'unit' => 'unit',
            'photo' => $this->fakePng('new.png'),
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]));

        $item->refresh();
        $this->assertNotSame($oldPhotoPath, $item->photo_path);
        Storage::disk('public')->assertMissing($oldPhotoPath);
        Storage::disk('public')->assertExists($item->photo_path);

        $currentPhotoPath = $item->photo_path;
        $this->put(route('inventory.update', $item), [
            'type' => InventoryItem::TYPE_ASSET,
            'category_id' => $category->id,
            'item_number' => '001',
            'name' => 'Printer',
            'quantity' => 1,
            'unit' => 'unit',
            'remove_photo' => '1',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]));

        $this->assertNull($item->fresh()->photo_path);
        Storage::disk('public')->assertMissing($currentPhotoPath);

        $photoPath = $this->fakePng('delete.png')->store('inventory', 'public');
        $item->update(['photo_path' => $photoPath]);

        $this->delete(route('inventory.destroy', $item))
            ->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]));

        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_non_image_file_is_rejected_as_item_photo(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Alat tulis kantor', 'code' => 'ATK']);

        $this->from(route('inventory.create'))
            ->post(route('inventory.store'), [
                'type' => InventoryItem::TYPE_CONSUMABLE,
                'category_id' => $category->id,
                'item_number' => '012',
                'name' => 'Kertas A4',
                'quantity' => 5,
                'unit' => 'rim',
                'photo' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain'),
            ])
            ->assertRedirect(route('inventory.create'))
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('inventory_items', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('inventory'));
    }

    private function fakePng(string $name): UploadedFile
    {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aXioAAAAASUVORK5CYII=',
            true
        );

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
