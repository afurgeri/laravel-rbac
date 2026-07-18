<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        foreach (['roles', 'permissions'] as $collection) {
            Schema::connection('mongodb')->create($collection, static function (Blueprint $blueprint): void {});
        }

        Schema::connection('mongodb')->table('roles', static function (Blueprint $blueprint): void {
            $blueprint->unique('name');
            $blueprint->index('permission_ids');
        });

        Schema::connection('mongodb')->table('permissions', static function (Blueprint $blueprint): void {
            $blueprint->unique('name');
            $blueprint->index('role_ids');
        });
    }

    public function down(): void
    {
        foreach (['roles', 'permissions'] as $collection) {
            Schema::connection('mongodb')->dropIfExists($collection);
        }
    }
};
