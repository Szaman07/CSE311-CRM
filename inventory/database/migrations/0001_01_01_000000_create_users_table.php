<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->collation('utf8mb4_bin');
            $table->string('password');
            $table->string('role', 20);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unique('email', 'uq_users_email');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT ck_users_role CHECK (role IN ('manager','sales_clerk'))");
        DB::statement('ALTER TABLE users ADD CONSTRAINT ck_users_active CHECK (is_active IN (0,1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
