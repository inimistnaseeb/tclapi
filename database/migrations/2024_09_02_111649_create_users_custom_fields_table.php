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
        Schema::create('users_custom_fields', function (Blueprint $table) {
            $table->id(); // id (int(11), auto-increment, primary key)
            $table->string('field_name', 300)->nullable(); // field_name (varchar(300), nullable)
            $table->string('field_type', 100)->nullable(); // field_type (varchar(100), nullable)
            $table->unsignedInteger('field_list_type')->nullable(); // field_list_type (int(11), nullable)
            $table->unsignedInteger('status')->default(1); // status (int(11), default 1)
            $table->boolean('restricted')->default(false); // restricted (tinyint(1), default 0)
            $table->dateTime('created')->nullable(); // created (datetime, nullable)
            $table->dateTime('modified')->nullable(); // modified (datetime, nullable)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_custom_fields');
    }
};
