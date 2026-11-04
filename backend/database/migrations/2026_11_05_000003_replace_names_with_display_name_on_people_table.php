<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('display_name')->nullable();
        });

        DB::table('people')->update(['display_name' => DB::raw("first_name || ' ' || last_name")]);

        Schema::table('people', function (Blueprint $table) {
            $table->string('display_name')->nullable(false)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
        });

        DB::table('people')->update(['first_name' => DB::raw('display_name'), 'last_name' => '']);

        Schema::table('people', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->dropColumn('display_name');
            $table->index(['last_name', 'first_name']);
        });
    }
};
