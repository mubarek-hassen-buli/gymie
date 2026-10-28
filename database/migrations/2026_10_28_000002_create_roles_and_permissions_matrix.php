<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Ensure roles exist
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $frontDesk = Role::firstOrCreate(['name' => 'front_desk', 'guard_name' => 'web']);
        $trainer = Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        // 2. Front Desk Permissions Matrix
        $frontDeskPermissions = [
            'ViewAny:Enquiry', 'View:Enquiry', 'Create:Enquiry', 'Update:Enquiry',
            'ViewAny:FollowUp', 'View:FollowUp', 'Create:FollowUp', 'Update:FollowUp',
            'ViewAny:Member', 'View:Member', 'Create:Member', 'Update:Member',
            'ViewAny:Subscription', 'View:Subscription', 'Create:Subscription', 'Update:Subscription',
            'ViewAny:Invoice', 'View:Invoice',
            'ViewAny:Plan', 'View:Plan',
            'ViewAny:Service', 'View:Service',
            'ViewAny:TrainingSession', 'View:TrainingSession', 'Create:TrainingSession', 'Update:TrainingSession',
            'ViewAny:Exercise', 'View:Exercise',
            'View:MembershipMetricsWidget',
            'View:MembershipOverviewSubscriptionsTableWidget',
            'View:ExpiringSoonSubscriptionsTableWidget',
        ];

        // 3. Trainer Permissions Matrix
        $trainerPermissions = [
            'ViewAny:TrainingSession', 'View:TrainingSession', 'Create:TrainingSession', 'Update:TrainingSession',
            'ViewAny:WorkoutProgram', 'View:WorkoutProgram', 'Create:WorkoutProgram', 'Update:WorkoutProgram', 'Delete:WorkoutProgram',
            'ViewAny:Exercise', 'View:Exercise', 'Create:Exercise', 'Update:Exercise',
            'ViewAny:Member', 'View:Member',
        ];

        // 4. Member Permissions Matrix
        $memberPermissions = [
            'ViewAny:TrainingSession', 'View:TrainingSession',
            'ViewAny:WorkoutProgram', 'View:WorkoutProgram',
            'ViewAny:Exercise', 'View:Exercise',
            'ViewAny:Invoice', 'View:Invoice',
        ];

        $this->syncRolePermissions($frontDesk, $frontDeskPermissions);
        $this->syncRolePermissions($trainer, $trainerPermissions);
        $this->syncRolePermissions($member, $memberPermissions);

        // Ensure super_admin has all permissions
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->all();
        if (! empty($allPermissions)) {
            $superAdmin->syncPermissions($allPermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::whereIn('name', ['front_desk', 'member'])->where('guard_name', 'web')->delete();
    }

    /**
     * Sync permissions to the specified role, ensuring permission records exist.
     *
     * @param  list<string>  $permissionNames
     */
    private function syncRolePermissions(Role $role, array $permissionNames): void
    {
        $permissionModels = collect($permissionNames)
            ->map(fn (string $permissionName): Permission => Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]))
            ->all();

        $role->syncPermissions($permissionModels);
    }
};
