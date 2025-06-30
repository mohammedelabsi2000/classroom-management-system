<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID for primary key
            $table->uuid('class_id'); // Referenced from Class Management Service [cite: 66]
            $table->uuid('teacher_id'); // Referenced from User Management Service [cite: 66]
            $table->string('title'); [cite: 66]
            $table->text('description')->nullable(); [cite: 66]
            $table->timestamp('due_date'); [cite: 66]
            $table->integer('max_score'); [cite: 66]
            $table->string('assignment_type'); // e.g., HOMEWORK, QUIZ, PROJECT [cite: 66]
            $table->string('status')->default('ACTIVE'); // e.g., ACTIVE, DRAFT, CLOSED [cite: 66]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};