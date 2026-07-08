<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\BelongsToTenant;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $job_title
 * @property string|null $username
 * @property string $email
 * @property string|null $phone
 * @property string|null $photo
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'job_title',
        'username',
        'email',
        'phone',
        'password',
        'photo',
        'role',
        'company_id',
        'email_verified_at',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the ID of the branch assigned to the user's employee profile.
     */
    public function getAssignedBranchId()
    {
        if ($this->role === 'admin') return null;
        $employee = $this->employee;
        if (!$employee || !$employee->branch) return null;
        return \App\Models\Branch::where('name', $employee->branch)->value('id');
    }

    /**
     * Get the ID of the store assigned to the user's employee profile.
     */
    public function getAssignedStoreId()
    {
        if ($this->role === 'admin') return null;
        $employee = $this->employee;
        if (!$employee || !$employee->store) return null;
        return \App\Models\Store::where('name', $employee->store)->value('id');
    }

    /**
     * Check if the user has a specific permission.
     * 
     * @param string $module The module name (e.g. 'Sales & POS', 'Accounting')
     * @param string $action The action name (e.g. 'view', 'create', 'edit', 'delete')
     * @return bool
     */
    public function hasPermission($module, $action = 'view')
    {
        $currentRole = strtolower(trim((string)$this->role));
        
        // Super Admin has all permissions
        if ($currentRole === 'super admin' || $currentRole === 'admin') {
            return true;
        }

        // Get permissions from the role table (Case-insensitive match)
        $role = \App\Models\Role::whereRaw('LOWER(name) = ?', [$currentRole])->first();
        
        if (!$role || empty($role->permissions)) {
            return false;
        }

        $permissions = $role->permissions;
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        // Check if module exists in permissions
        if (!is_array($permissions) || !isset($permissions[$module])) {
            return false;
        }

        // If the action is 'view', allow it if ANY permission is set for this module
        if ($action === 'view') {
            return !empty($permissions[$module]);
        }

        // Check if specific action exists for that module
        return in_array($action, (array)$permissions[$module]);
    }
}
