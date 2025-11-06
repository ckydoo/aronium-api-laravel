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
        // Products table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('aronium_product_id')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->integer('tax_id')->nullable();
            $table->string('tax_code')->nullable();
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('unit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('aronium_product_id');
            $table->index('code');
            $table->index('barcode');
            $table->index('is_active');
        });

        // Stock/Inventory table
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->nullable();
            $table->decimal('reorder_quantity', 10, 2)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('product_id');
            $table->index(['product_id', 'company_id']);
            $table->index('quantity');
        });

        // Purchases table
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('aronium_document_id')->unique();
            $table->string('document_number');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->dateTime('date_created');
            $table->integer('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->integer('user_id')->nullable();
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('aronium_document_id');
            $table->index('document_number');
            $table->index('date_created');
            $table->index('supplier_id');
            $table->index('status');
        });

        // Purchase items table
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->integer('aronium_product_id');
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_id');
            $table->index('aronium_product_id');
        });

        // Z-Reports table (daily sales summary)
        Schema::create('z_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('aronium_report_id')->unique()->nullable();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('report_date');
            $table->string('report_number');
            $table->integer('device_id')->nullable();
            $table->string('device_name')->nullable();
            
            // Sales summary
            $table->integer('total_transactions')->default(0);
            $table->integer('total_items_sold')->default(0);
            $table->decimal('gross_sales', 10, 2)->default(0);
            $table->decimal('discounts', 10, 2)->default(0);
            $table->decimal('returns', 10, 2)->default(0);
            $table->decimal('net_sales', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            
            // Payment breakdown (JSON)
            $table->json('payment_breakdown')->nullable();
            
            // Tax breakdown (JSON)
            $table->json('tax_breakdown')->nullable();
            
            // Opening and closing
            $table->decimal('opening_cash', 10, 2)->nullable();
            $table->decimal('closing_cash', 10, 2)->nullable();
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->decimal('cash_difference', 10, 2)->nullable();
            
            // Timestamps
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('opened_by')->nullable();
            $table->integer('closed_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('report_date');
            $table->index('report_number');
            $table->index('device_id');
            $table->index(['company_id', 'report_date']);
        });

        // Stock movements table (for tracking inventory changes)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['sale', 'purchase', 'adjustment', 'return', 'transfer']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('quantity_before', 10, 2);
            $table->decimal('quantity_after', 10, 2);
            $table->integer('reference_id')->nullable(); // ID of related document (sale, purchase, etc)
            $table->string('reference_type')->nullable(); // Type of document
            $table->text('notes')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamp('movement_date');
            $table->timestamps();
            
            // Indexes
            $table->index('product_id');
            $table->index('movement_type');
            $table->index('movement_date');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('z_reports');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('products');
    }
};
