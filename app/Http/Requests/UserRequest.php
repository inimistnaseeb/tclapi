<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
       return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // dd($this->user);
        /*

            $table->integer('time_clock')->nullable();
            $table->string('firm', 50)->nullable();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('user_title')->nullable();
            $table->integer('role_id')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->string('email_address', 45)->nullable();
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

            $table->dateTime('created');
            $table->dateTime('modified');
            $table->boolean('deleted')->default(false);
            $table->dateTime('deleted_date')->nullable();

            $table->tinyInteger('upload_count')->default(0);
            $table->integer('course_count')->nullable();
            $table->string('avatar')->nullable();
            $table->string('theme', 15)->nullable();
            $table->date('pw_updated')->nullable();
            $table->mediumText('pw_used')->nullable();
            $table->string('dual_auth', 6)->nullable();
            $table->boolean('require_dual_auth')->default(true);
            $table->date('last_dual_auth')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('background_check_date')->nullable();
            $table->date('original_background_check_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('reason_for_deletion', 250)->nullable();
            $table->mediumText('clients')->nullable();
            $table->string('location')->nullable();
            $table->string('schedule')->nullable();
            $table->string('status', 50)->nullable();
            $table->integer('restricted_fields_access')->default(0);
            $table->boolean('view_all_attestations')->default(false);
            $table->integer('creator_id')->nullable();
            $table->boolean('auto_created')->default(false);
        */
        return [
            'dept_no' => 'Nullable|integer',
            'time_clock' => 'Nullable|integer',
            'firm' => 'Nullable|string|max:50',
            'username' => 'Required|string',
            'first_name' => 'Required|string',
            'last_name' => 'Required|string',
            'password' => 'Required|string',
            'user_title' => 'Nullable|string',
            'role_id' => 'Required|integer',
            'supervisor_id' => 'Nullable|integer',
            'email_address' => "Required|email|unique:users,email,{$this->user->id},id",
            'alternate_email1' => 'Sometimes|email',
            'enforce_domain_check' => 'Sometimes|boolean',
            'mobile_phone_number' => 'Nullable|integer|max:45',
            'office_phone_number' => 'Nullable|integer|max:45',
            'email_subscription' => 'Sometimes|boolean',
            'department_id' => 'Nullable|integer',
            'view_team_compliance' => 'Sometimes|boolean',
            'view_all_compliance' => 'Sometimes|boolean',
            'management_attestation' => 'Sometimes|boolean',
            'tokenhash' => 'Nullable|string|max:55',
            'upload_count' => 'Nullable|integer',
            'course_count' => 'Nullable|integer',
            'avatar' => 'Nullable|string',
            'pw_updated' => 'Nullable|date',
            'pw_used' => 'Nullable|string',
            'dual_auth' => 'Nullable|string|max:6',
            'require_dual_auth' => 'Nullable|boolean',
            'last_dual_auth' => 'Nullable|date',
            'hire_date' => 'Nullable|date',
            'background_check_date' => 'Nullable|date',
            'original_background_check_date' => 'Nullable|date',
            'termination_date' => 'Nullable|date',
            'reason_for_deletion' => 'Nullable|string|max:250',
            'clients' => 'Nullable|string',
            'location' => 'Nullable|string',
            'schedule' => 'Nullable|string',
            'status' => 'Nullable|string|max:50',
            'restricted_fields_access' => 'Nullable|string',
            'view_all_attestations' => 'Nullable|boolean',
            'creator_id' => 'Nullable|integer',
            'auto_created' => 'Nullable|boolean',
            'time_clock' => 'Nullable|integer',
        ];
    }
}
