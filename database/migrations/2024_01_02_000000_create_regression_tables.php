<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('case_label')->nullable();
            $table->text('description')->nullable();
            $table->string('x_label', 60)->default('X');
            $table->string('x_unit', 50)->nullable();
            $table->string('y_label', 60)->default('Y');
            $table->string('y_unit', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('data_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->decimal('x_value', 18, 6);
            $table->decimal('y_value', 18, 6);
            $table->timestamps();
        });

        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('x_input', 18, 6);
            $table->decimal('y_predicted', 18, 6);
            $table->decimal('slope', 18, 8);
            $table->decimal('intercept', 18, 8);
            $table->decimal('r_squared', 10, 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('data_rows');
        Schema::dropIfExists('datasets');
    }
};
