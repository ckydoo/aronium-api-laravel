<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')
                  ->nullable() 
                  ->constrained() // Assumes your companies table is named 'companies' and has an 'id' column
                  ->after('id'); // Place it after the 'id' column for better organization
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // You must drop the foreign key constraint before dropping the column
            $table->dropForeign(['company_id']); 
            $table->dropColumn('company_id');
        });
    }
};