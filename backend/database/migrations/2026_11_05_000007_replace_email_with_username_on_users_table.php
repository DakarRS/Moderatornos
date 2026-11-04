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
            $table->string('username', 50)->nullable()->unique();
        });

        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'email']) as $user) {
            $username = $this->usernameFromEmail($user->email, $taken);
            $taken[] = $username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->change();
            $table->dropColumn(['email', 'email_verified_at']);
        });

        // Password resets went by email, and this table would keep addresses around.
        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->dropColumn('username');
        });
    }

    /** @param  list<string>  $taken */
    private function usernameFromEmail(string $email, array $taken): string
    {
        $base = preg_replace('/[^a-z0-9._-]/', '-', strtolower(explode('@', $email)[0]));
        $base = trim($base, '.-_');
        $base = substr(strlen($base) < 3 ? trim("{$base}-user", '-') : $base, 0, 46);

        $username = $base;
        for ($suffix = 2; in_array($username, $taken, true); $suffix++) {
            $username = "{$base}-{$suffix}";
        }

        return $username;
    }
};
