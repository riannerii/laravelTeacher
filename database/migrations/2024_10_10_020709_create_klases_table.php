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
        Schema::create('klases', function (Blueprint $table) {
            $table->id('class_id')->primary();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('section_id'); 
            $table->string('room');
            $table->string('schedule');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klases');
    }
};
