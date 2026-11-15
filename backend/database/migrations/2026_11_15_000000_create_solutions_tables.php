<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create extension if not exists pg_trgm');

        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('symptoms')->nullable();
            $table->text('cause')->nullable();
            $table->text('fix');
            $table->string('edition', 16);
            $table->string('versions')->nullable();
            $table->date('last_verified_on')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        // Title words weigh more, so a title hit ranks above the same word in the body.
        DB::statement(<<<'SQL'
            alter table solutions add column search_vector tsvector generated always as (
                setweight(to_tsvector('english', title), 'A') ||
                setweight(to_tsvector('english', coalesce(symptoms, '') || ' ' || coalesce(cause, '') || ' ' || fix), 'B')
            ) stored
        SQL);
        DB::statement('create index solutions_search_vector_index on solutions using gin (search_vector)');
        DB::statement('create index solutions_title_trgm_index on solutions using gin (title gin_trgm_ops)');

        Schema::create('solution_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->string('pattern', 500);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('solution_tag', function (Blueprint $table) {
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->index()->constrained()->cascadeOnDelete();
            $table->primary(['solution_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solution_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('solution_signatures');
        Schema::dropIfExists('solutions');
    }
};
