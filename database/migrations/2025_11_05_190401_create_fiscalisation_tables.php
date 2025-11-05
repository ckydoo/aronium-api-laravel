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
        // Companies table
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id')->unique();
            $table->string('vat_number')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Sales table
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id')->unique();
            $table->string('document_number');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->dateTime('date_created');
            $table->decimal('total', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->integer('customer_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->enum('status', ['pending', 'fiscalized', 'error'])->default('pending');
            
            // Fiscal data fields
            $table->text('fiscal_signature')->nullable();
            $table->text('qr_code')->nullable();
            $table->string('fiscal_invoice_number')->nullable();
            $table->dateTime('fiscalized_at')->nullable();
            $table->json('tax_details')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('document_id');
            $table->index('document_number');
            $table->index('date_created');
            $table->index('status');
            $table->index('fiscalized_at');
        });

        // Sale items table
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->integer('product_id');
            $table->string('product_name');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->integer('tax_id')->nullable();
            $table->string('tax_code')->nullable();
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('sale_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('companies');
    }
};