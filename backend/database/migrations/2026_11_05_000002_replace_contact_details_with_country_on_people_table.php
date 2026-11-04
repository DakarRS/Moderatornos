<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Intl\Countries;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->index();
        });

        // Locations end in a country code ("Berlin, DE"), so keep that before dropping them.
        DB::table('people')->whereNotNull('location')->select('id', 'location')->orderBy('id')->each(function ($person) {
            if (preg_match('/\b([A-Z]{2})$/', $person->location, $match) && Countries::exists($match[1])) {
                DB::table('people')->where('id', $person->id)->update(['country_code' => $match[1]]);
            }
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'location']);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->dropColumn('country_code');
        });
    }
};
