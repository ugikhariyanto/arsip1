<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->date('condition_date')->nullable()->after('condition');
            $table->text('follow_up')->nullable()->after('condition_date');
            $table->string('calibration_status', 20)->nullable()->after('follow_up');
            $table->date('calibration_date')->nullable()->after('calibration_status');
            $table->index('condition');
        });

        // Selaraskan kondisi lama dengan pilihan keadaan yang baru.
        DB::table('inventory_items')->where('condition', 'Perlu perbaikan')->update(['condition' => 'Rusak sedang']);
        DB::table('inventory_items')->where('condition', 'Rusak')->update(['condition' => 'Rusak berat']);
        DB::table('inventory_items')->whereNull('condition')->update(['condition' => 'Baik']);
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex(['condition']);
            $table->dropColumn(['condition_date', 'follow_up', 'calibration_status', 'calibration_date']);
        });
    }
};
