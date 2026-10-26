<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 100);
            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit', 30)->default('unit');
            $table->string('location', 150)->nullable();
            $table->string('condition', 100)->nullable();
            $table->date('acquired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
