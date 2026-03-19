<?php

use App\Support\UserCredentialSupport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
        });

        DB::table('users')
            ->orderBy('id')
            ->select('id', 'email', 'name', 'role')
            ->get()
            ->each(function ($user) {
                $seed = $user->email ?: $user->name ?: $user->role ?: 'user';

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'username' => UserCredentialSupport::generateUniqueUsername($seed, (int) $user->id),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
