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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            //$table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->integer('dept_no')->nullable();
            $table->integer('time_clock')->nullable();
            $table->string('firm', 50)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
           // $table->string('password', 255)->nullable();
            $table->string('user_title', 255)->nullable();
            $table->integer('role_id')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->string('alternate_email1', 150)->nullable();
            $table->boolean('enforce_domain_check')->nullable();
            $table->string('mobile_phone_number', 45)->nullable();
            $table->string('office_phone_number', 45)->nullable();
            $table->string('backup_username', 45)->nullable();
            $table->boolean('email_subscription')->nullable();
            $table->integer('department_id')->nullable();
            $table->boolean('view_team_compliance')->nullable();
            $table->boolean('view_all_compliance')->nullable();
            $table->boolean('management_attestation')->nullable();
            $table->string('tokenhash', 55)->nullable();
          //  $table->dateTime('created');
            $table->dateTime('modified');
            $table->boolean('deleted')->default(0);
            $table->dateTime('deleted_date')->nullable();
            $table->tinyInteger('upload_count')->default(0);
            $table->integer('course_count')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('theme', 15)->nullable();
            $table->date('pw_updated')->nullable();
            $table->mediumText('pw_used')->nullable();
            $table->string('dual_auth', 6)->nullable();
            $table->boolean('require_dual_auth')->default(1);
            $table->date('last_dual_auth')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('background_check_date')->nullable();
            $table->date('original_background_check_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('reason_for_deletion', 250)->nullable();
            $table->mediumText('clients')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('schedule', 255)->nullable();
            $table->string('status', 50)->nullable();
            $table->tinyInteger('restricted_fields_access')->default(0);
            $table->boolean('view_all_attestations')->default(0);
            $table->integer('creator_id')->nullable();
            $table->boolean('auto_created')->default(0);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
