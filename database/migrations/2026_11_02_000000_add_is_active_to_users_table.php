<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users can be deactivated without deleting them. Applications that already
 * added the column keep it untouched.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = (string) config('rbac.tables.users', 'users');

        if (! Schema::hasTable($users) || Schema::hasColumn($users, 'is_active')) {
            return;
        }

        Schema::table($users, function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * The column is kept: it may have existed before this migration ran, and dropping it
     * would reactivate every deactivated user.
     */
    public function down(): void
    {
        //
    }
};
