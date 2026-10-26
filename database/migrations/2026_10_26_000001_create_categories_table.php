<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained()->nullOnDelete();
            $table->string('item_number', 10)->nullable()->after('code');
            $table->unique(['category_id', 'item_number']);
        });

        $usedCodes = [];

        DB::table('inventory_items')
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->get()
            ->each(function ($row) use (&$usedCodes) {
                $name = trim($row->category);

                if ($name === '') {
                    return;
                }

                $baseCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($name)), 0, 3));
                $baseCode = $baseCode !== '' ? $baseCode : 'KAT';
                $code = $baseCode;
                $suffix = 2;

                while (in_array($code, $usedCodes, true)) {
                    $code = Str::substr($baseCode, 0, 10 - strlen((string) $suffix)).$suffix;
                    $suffix++;
                }

                $usedCodes[] = $code;
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => $name,
                    'code' => $code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('inventory_items')->where('category', $row->category)->update([
                    'category_id' => $categoryId,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'item_number']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('item_number');
        });

        Schema::dropIfExists('categories');
    }
};
