<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });

        // Capitals are kept for display, but "Dobbie" and "dobbie" must be the same account.
        DB::statement('create unique index users_username_lower_unique on users (lower(username))');
    }

    public function down(): void
    {
        DB::statement('drop index users_username_lower_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }
};
