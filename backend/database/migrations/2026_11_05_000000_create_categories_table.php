<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        // Existing people need somewhere to go before the column can become required.
        if (DB::table('people')->exists()) {
            $now = now();
            $moderatorId = DB::table('categories')->insertGetId(['name' => 'Moderator', 'slug' => 'moderator', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('categories')->insert(['name' => 'Former Moderator', 'slug' => 'former-moderator', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('people')->update(['category_id' => $moderatorId]);
        }

        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->dropColumn(['job_title', 'company']);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('job_title')->nullable();
            $table->string('company')->nullable();
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
