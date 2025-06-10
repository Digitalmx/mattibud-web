<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the spatial index exists and drop it
        $indexes = DB::select("SHOW INDEX FROM stores WHERE Key_name = 'idx_stores_location'");
        if (!empty($indexes)) {
            DB::statement('DROP INDEX idx_stores_location ON stores');
        }
        
        // Alter the location column to be nullable (MariaDB syntax)
        DB::statement('ALTER TABLE stores MODIFY COLUMN location POINT NULL');
        
        // Note: Spatial indexes cannot be created on nullable columns in MySQL/MariaDB
        // We'll skip recreating the index for now since location is not being used
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make the location column NOT NULL again first (MariaDB syntax)
        DB::statement('ALTER TABLE stores MODIFY COLUMN location POINT NOT NULL');
        
        // Recreate the spatial index
        DB::statement('CREATE SPATIAL INDEX idx_stores_location ON stores(location)');
    }
};
