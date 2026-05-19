<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::beginTransaction();

        try {
            DB::statement(<<<'SQL'
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR NOT NULL,
                    email VARCHAR NOT NULL,
                    username VARCHAR NULL,
                    email_verified_at DATETIME NULL,
                    password VARCHAR NOT NULL,
                    role VARCHAR NOT NULL DEFAULT 'admin' CHECK (role IN ('admin','teacher','cashier','parent','student')),
                    phone VARCHAR NULL,
                    address TEXT NULL,
                    profile_photo VARCHAR NULL,
                    is_active TINYINT NOT NULL DEFAULT 1,
                    remember_token VARCHAR NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            SQL);

            DB::statement("CREATE UNIQUE INDEX users_new_email_unique ON users_new(email)");
            DB::statement("CREATE UNIQUE INDEX users_new_username_unique ON users_new(username)");

            DB::statement(<<<'SQL'
                INSERT INTO users_new (
                    id,
                    name,
                    email,
                    username,
                    email_verified_at,
                    password,
                    role,
                    phone,
                    address,
                    profile_photo,
                    is_active,
                    remember_token,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    name,
                    email,
                    username,
                    email_verified_at,
                    password,
                    CASE
                        WHEN role IN ('admin','teacher','cashier','parent','student') THEN role
                        ELSE 'admin'
                    END,
                    phone,
                    address,
                    profile_photo,
                    is_active,
                    remember_token,
                    created_at,
                    updated_at
                FROM users
            SQL);

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::beginTransaction();

        try {
            DB::statement(<<<'SQL'
                CREATE TABLE users_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR NOT NULL,
                    email VARCHAR NOT NULL,
                    username VARCHAR NULL,
                    email_verified_at DATETIME NULL,
                    password VARCHAR NOT NULL,
                    role VARCHAR NOT NULL DEFAULT 'admin' CHECK (role IN ('admin','teacher','parent')),
                    phone VARCHAR NULL,
                    address TEXT NULL,
                    profile_photo VARCHAR NULL,
                    is_active TINYINT NOT NULL DEFAULT 1,
                    remember_token VARCHAR NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            SQL);

            DB::statement("CREATE UNIQUE INDEX users_old_email_unique ON users_old(email)");
            DB::statement("CREATE UNIQUE INDEX users_old_username_unique ON users_old(username)");

            DB::statement(<<<'SQL'
                INSERT INTO users_old (
                    id,
                    name,
                    email,
                    username,
                    email_verified_at,
                    password,
                    role,
                    phone,
                    address,
                    profile_photo,
                    is_active,
                    remember_token,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    name,
                    email,
                    username,
                    email_verified_at,
                    password,
                    CASE
                        WHEN role IN ('admin','teacher','parent') THEN role
                        ELSE 'parent'
                    END,
                    phone,
                    address,
                    profile_photo,
                    is_active,
                    remember_token,
                    created_at,
                    updated_at
                FROM users
            SQL);

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_old RENAME TO users');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
