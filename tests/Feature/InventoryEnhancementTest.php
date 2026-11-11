<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_items_save_location_and_condition(): void
    {
        $category = Category::create(['name' => 'Furnitur', 'code' => 'FUR']);
        $location = Location::create(['name' => 'Ruang Manajemen']);

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_FACILITY,
            'category_id' => $category->id,
            'name' => 'Meja Rapat',
            'quantity' => 2,
            'unit' => 'unit',
            'location_id' => $location->id,
            'condition' => InventoryItem::CONDITION_GOOD,
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_FACILITY]));

        $this->assertDatabaseHas('inventory_items', [
            'code' => 'FUR-0001',
            'type' => InventoryItem::TYPE_FACILITY,
            'location_id' => $location->id,
            'location' => 'Ruang Manajemen',
            'condition' => InventoryItem::CONDITION_GOOD,
        ]);
    }

    public function test_damaged_condition_requires_date_and_saves_follow_up(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);

        $this->from(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->post(route('inventory.store'), [
                'type' => InventoryItem::TYPE_ASSET,
                'category_id' => $category->id,
                'name' => 'Printer',
                'quantity' => 1,
                'unit' => 'unit',
                'condition' => InventoryItem::CONDITION_MAJOR_DAMAGE,
            ])
            ->assertRedirect(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->assertSessionHasErrors('condition_date');

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_ASSET,
            'category_id' => $category->id,
            'name' => 'Printer',
            'quantity' => 1,
            'unit' => 'unit',
            'condition' => InventoryItem::CONDITION_MAJOR_DAMAGE,
            'condition_date' => '2026-11-10',
            'follow_up' => 'Diajukan perbaikan ke bagian umum.',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]));

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Printer',
            'condition' => InventoryItem::CONDITION_MAJOR_DAMAGE,
            'condition_date' => '2026-11-10 00:00:00',
            'follow_up' => 'Diajukan perbaikan ke bagian umum.',
        ]);
    }

    public function test_asset_calibration_requires_a_date_when_already_done(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);

        $this->from(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->post(route('inventory.store'), [
                'type' => InventoryItem::TYPE_ASSET,
                'category_id' => $category->id,
                'name' => 'Timbangan Digital',
                'quantity' => 1,
                'unit' => 'unit',
                'calibration_status' => InventoryItem::CALIBRATION_DONE,
            ])
            ->assertRedirect(route('inventory.create', ['type' => InventoryItem::TYPE_ASSET]))
            ->assertSessionHasErrors('calibration_date');

        $this->post(route('inventory.store'), [
            'type' => InventoryItem::TYPE_ASSET,
            'category_id' => $category->id,
            'name' => 'Timbangan Digital',
            'quantity' => 1,
            'unit' => 'unit',
            'calibration_status' => InventoryItem::CALIBRATION_DONE,
            'calibration_date' => '2026-10-01',
        ])->assertRedirect(route('inventory.index', ['type' => InventoryItem::TYPE_ASSET]));

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Timbangan Digital',
            'calibration_status' => InventoryItem::CALIBRATION_DONE,
            'calibration_date' => '2026-10-01 00:00:00',
        ]);
    }

    public function test_locations_can_be_managed_and_cannot_be_deleted_while_used(): void
    {
        $this->get(route('locations.index'))->assertOk()->assertSeeText('Lokasi / ruangan');

        $this->post(route('locations.store'), ['name' => 'Ruang Farmasi'])
            ->assertRedirect(route('locations.index'));

        $location = Location::query()->where('name', 'Ruang Farmasi')->firstOrFail();
        $category = Category::create(['name' => 'Obat', 'code' => 'OBT']);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_FACILITY,
            'code' => 'OBT-0001',
            'item_number' => '0001',
            'name' => 'Lemari Obat',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
            'location' => $location->name,
            'location_id' => $location->id,
            'condition' => InventoryItem::CONDITION_GOOD,
        ]);

        $this->delete(route('locations.destroy', $location))
            ->assertRedirect(route('locations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_report_pages_and_all_export_formats_are_available(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);
        $location = Location::create(['name' => 'Ruang Manajemen']);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'ELK-0001',
            'item_number' => '0001',
            'name' => 'Komputer',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
            'location' => $location->name,
            'location_id' => $location->id,
            'condition' => InventoryItem::CONDITION_GOOD,
            'price' => 5000000,
            'calibration_status' => InventoryItem::CALIBRATION_DONE,
            'calibration_date' => '2026-10-01',
        ]);

        $this->get(route('reports.items'))->assertOk()->assertSeeText('Laporan aset / sarana dan prasarana');
        $this->get(route('reports.kir', ['location_id' => $location->id]))
            ->assertOk()
            ->assertSeeText('KARTU INVENTARIS RUANGAN')
            ->assertSeeText('RUANGAN MANAJEMEN');
        $this->get(route('reports.conditions', ['condition' => InventoryItem::CONDITION_GOOD]))
            ->assertOk()
            ->assertSeeText('LAPORAN KEADAAN BARANG');

        foreach (['xlsx', 'docx', 'pdf'] as $format) {
            $response = $this->get(route('reports.items.export', ['format' => $format]));
            $response->assertOk();

            if ($format === 'pdf') {
                $this->assertStringStartsWith('%PDF', $response->getContent());
            } else {
                $this->assertStringStartsWith('PK', $response->getContent());
            }
        }
    }

    public function test_excel_export_keeps_worksheet_elements_in_schema_order(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'code' => 'ELK']);
        InventoryItem::create([
            'type' => InventoryItem::TYPE_ASSET,
            'code' => 'ELK-0001',
            'item_number' => '0001',
            'name' => 'Komputer',
            'category' => $category->name,
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'unit',
            'condition' => InventoryItem::CONDITION_GOOD,
        ]);

        $content = $this->get(route('reports.items.export', ['format' => 'xlsx']))
            ->assertOk()
            ->getContent();

        // Entri ZIP laporan disimpan tanpa kompresi, jadi XML worksheet dapat
        // diperiksa langsung. Excel menolak berkas bila mergeCells mendahului
        // autoFilter, dan hanya menampilkan lembar kosong.
        $this->assertStringContainsString('<dimension ref="A1:', $content);
        $this->assertStringContainsString('<autoFilter ', $content);
        $this->assertStringContainsString('<mergeCells ', $content);
        $this->assertLessThan(
            strpos($content, '<mergeCells '),
            strpos($content, '<autoFilter '),
            'Elemen autoFilter harus muncul sebelum mergeCells pada worksheet.'
        );
    }

    public function test_kir_export_requires_a_room(): void
    {
        $this->get(route('reports.kir.export', ['format' => 'xlsx']))
            ->assertRedirect(route('reports.kir'))
            ->assertSessionHas('error');
    }
}
