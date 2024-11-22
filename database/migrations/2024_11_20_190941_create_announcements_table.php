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
        Schema::create('announcements', callback: function (Blueprint $table): void {
            $table->id('ancmnt_id'); 
            $table->unsignedBigInteger('admin_id'); 
            $table->unsignedBigInteger('class_id');
            $table->string('title');
            $table->text('announcement');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
