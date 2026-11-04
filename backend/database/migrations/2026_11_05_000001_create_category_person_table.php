<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_person', function (Blueprint $table) {
            // Restrict keeps a category from disappearing while people are still in it.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('person_id')->index()->constrained('people')->cascadeOnDelete();
            $table->primary(['category_id', 'person_id']);
        });

        DB::statement('insert into category_person (category_id, person_id) select category_id, id from people');

        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        DB::statement('update people set category_id = (select min(category_id) from category_person where person_id = people.id)');

        Schema::dropIfExists('category_person');
    }
};
