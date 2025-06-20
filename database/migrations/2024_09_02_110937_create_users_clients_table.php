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
        Schema::create('users_clients', function (Blueprint $table) {
            $table->id(); // equivalent to $table->bigIncrements('id');
            $table->unsignedInteger('user_id'); // for user_id column
            $table->unsignedInteger('client_id'); // for client_id column
            $table->timestamp('created')->nullable(); // equivalent to $table->timestamps(); if you want to use created_at and updated_at
            $table->boolean('deleted')->default(0); // default value set to 0
            $table->date('deleted_date')->nullable(); // nullable date field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_clients');
    }
};
