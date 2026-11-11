<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->timestamps();
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('location')->constrained()->nullOnDelete();
        });

        // Pindahkan lokasi lama yang masih berupa teks menjadi data ruangan.
        DB::table('inventory_items')
            ->select('location')
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->each(function (string $name) {
                $name = trim($name);

                if ($name === '') {
                    return;
                }

                $locationId = DB::table('locations')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('inventory_items')
                    ->where('location', $name)
                    ->update(['location_id' => $locationId]);
            });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('locations');
    }
};
