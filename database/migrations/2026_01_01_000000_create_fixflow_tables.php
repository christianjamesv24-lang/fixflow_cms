<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('email', 190)->unique();
                $table->string('password_hash', 255);
                $table->string('phone', 40)->nullable();
                $table->string('address', 255)->nullable();
                $table->enum('role', ['admin', 'technician', 'customer'])->default('customer')->index();
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->string('specialty', 120)->nullable();
                $table->text('bio')->nullable();
                $table->string('avatar_color', 20)->default('#2563eb');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('service_categories')) {
            Schema::create('service_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->string('icon', 40)->default('wrench');
                $table->string('short_description', 255)->nullable();
                $table->text('description')->nullable();
                $table->decimal('base_price', 10, 2)->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('service_requests')) {
            Schema::create('service_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ticket_no', 30)->unique();
                $table->unsignedInteger('customer_id');
                $table->unsignedInteger('category_id');
                $table->unsignedInteger('technician_id')->nullable();
                $table->enum('service_type', ['repair', 'maintenance', 'installation', 'inspection'])->default('repair');
                $table->string('title', 160);
                $table->text('description');
                $table->string('brand', 80)->nullable();
                $table->string('model', 80)->nullable();
                $table->string('serial_no', 80)->nullable();
                $table->string('address', 255);
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->date('preferred_date')->nullable();
                $table->string('preferred_time', 30)->nullable();
                $table->enum('status', ['pending', 'assigned', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('pending')->index();
                $table->decimal('estimated_cost', 10, 2)->nullable();
                $table->decimal('final_cost', 10, 2)->nullable();
                $table->dateTime('assigned_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('category_id')->references('id')->on('service_categories')->restrictOnDelete();
                $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('request_status_history')) {
            Schema::create('request_status_history', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('request_id');
                $table->string('status', 30);
                $table->text('note')->nullable();
                $table->unsignedInteger('changed_by')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('request_id')->references('id')->on('service_requests')->cascadeOnDelete();
                $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('request_id');
                $table->unsignedInteger('technician_id')->nullable();
                $table->date('scheduled_date');
                $table->time('scheduled_time');
                $table->integer('duration_minutes')->default(60);
                $table->enum('status', ['scheduled', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled')->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('request_id')->references('id')->on('service_requests')->cascadeOnDelete();
                $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('service_reports')) {
            Schema::create('service_reports', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('request_id')->unique();
                $table->unsignedInteger('technician_id')->nullable();
                $table->text('diagnosis')->nullable();
                $table->text('work_performed')->nullable();
                $table->text('parts_used')->nullable();
                $table->decimal('labor_hours', 4, 1)->nullable();
                $table->text('recommendations')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('request_id')->references('id')->on('service_requests')->cascadeOnDelete();
                $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('feedback')) {
            Schema::create('feedback', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('request_id')->unique();
                $table->unsignedInteger('customer_id');
                $table->unsignedInteger('technician_id')->nullable();
                $table->tinyInteger('rating')->default(5);
                $table->text('comment')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('request_id')->references('id')->on('service_requests')->cascadeOnDelete();
                $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('cms_pages')) {
            Schema::create('cms_pages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 160);
                $table->string('slug', 160)->unique();
                $table->string('excerpt', 255)->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title', 160)->nullable();
                $table->string('meta_description', 255)->nullable();
                $table->enum('status', ['published', 'draft'])->default('draft');
                $table->boolean('show_in_footer')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('email', 190);
                $table->string('phone', 40)->nullable();
                $table->string('subject', 160);
                $table->text('message');
                $table->enum('status', ['new', 'read', 'replied'])->default('new')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('setting_key', 80)->primary();
                $table->text('setting_value')->nullable();
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('action', 60);
                $table->string('description', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('service_reports');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('request_status_history');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('users');
    }
};
