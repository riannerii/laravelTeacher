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
        Schema::create('admins', function (Blueprint $table) {
            $table->id(column: 'admin_id');
            $table->string(column: 'fname');
            $table->string(column: 'lname');
            $table->string(column: 'mname');
            $table->string(column: 'role');
            $table->string(column: 'address');
            $table->string(column: 'admin_pic')->nullable();
            $table->string(column: 'email');
            $table->string(column: 'password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
