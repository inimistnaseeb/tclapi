<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    protected $primaryKey = 'iUid';
    public $incrementing = true;  // Set to false if it's not auto-incrementing
    protected $keyType = 'int';   // Set the key type (string, int, etc.)
    use HasFactory, Notifiable, HasApiTokens;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'iUid',
        'dept_no',
        'time_clock',
        'firm',
        'username',
        'first_name',
        'last_name',
        'user_title',
        'role_id',
        'supervisor_id',
        'alternate_email1',
        'enforce_domain_check',
        'mobile_phone_number',
        'office_phone_number',
        'backup_username',
        'email_subscription',
        'department_id',
        'view_team_compliance',
        'view_all_compliance',
        'management_attestation',
        'tokenhash',
        'created',
        'modified',
        'deleted',
        'deleted_date',
        'upload_count',
        'course_count',
        'avatar',
        'theme',
        'pw_updated',
        'pw_used',
        'dual_auth',
        'require_dual_auth',
        'last_dual_auth',
        'hire_date',
        'background_check_date',
        'original_background_check_date',
        'termination_date',
        'reason_for_deletion',
        'clients',
        'location',
        'schedule',
        'status',
        'restricted_fields_access',
        'view_all_attestations',
        'creator_id',
        'auto_created',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function uploads()
    {
        return $this->hasMany(Upload::class, 'foreign_key')
            ->where('model', 'User');
    }

    // Define the relationship to UsersClient model
    public function usersClients()
    {
        return $this->hasMany(UserClient::class, 'user_id');
    }

    // Define the relationship to UsersPlatform model
    public function usersPlatforms()
    {
        return $this->hasMany(UsersPlatform::class, 'user_id');
    }

    // Define the relationship to UserUserType model
    public function userUserTypes()
    {
        return $this->hasMany(UserUserType::class, 'user_id');
    }

    // Define the relationship to UserDepartment model
    public function userDepartments()
    {
        return $this->hasMany(UserDepartment::class, 'user_id');
    }

    // Define the relationship to UserActivity model
    public function userActivities()
    {
        return $this->hasMany(UserActivity::class, 'foreign_key');
    }

    // Define the relationship to UserActivity model where action_by is user_id
    public function activitiesBy()
    {
        return $this->hasMany(UserActivity::class, 'action_by');
    }

    // Define the relationship to TaskProcessOwner model
    public function taskProcessOwners()
    {
        return $this->hasMany(TaskProcessOwner::class, 'user_id');
    }

    // Define the relationship to UsersCustomFieldsValue model
    public function usersCustomFieldsValues()
    {
        return $this->hasMany(UsersCustomFieldsValue::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id')
            ->select(['id', 'name']);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id')
            ->select(['id', 'full_name', 'username', 'email_address']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id')
            ->select(['id', 'username', 'first_name', 'last_name', 'email_address']);
    }
}
