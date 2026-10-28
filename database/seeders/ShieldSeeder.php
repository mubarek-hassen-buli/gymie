<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = [
            [
                'name' => 'super_admin',
                'guard_name' => 'web',
                'permissions' => [],
            ],
            [
                'name' => 'front_desk',
                'guard_name' => 'web',
                'permissions' => [
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
                ],
            ],
            [
                'name' => 'trainer',
                'guard_name' => 'web',
                'permissions' => [
                    'ViewAny:TrainingSession', 'View:TrainingSession', 'Create:TrainingSession', 'Update:TrainingSession',
                    'ViewAny:WorkoutProgram', 'View:WorkoutProgram', 'Create:WorkoutProgram', 'Update:WorkoutProgram', 'Delete:WorkoutProgram',
                    'ViewAny:Exercise', 'View:Exercise', 'Create:Exercise', 'Update:Exercise',
                    'ViewAny:Member', 'View:Member',
                ],
            ],
            [
                'name' => 'member',
                'guard_name' => 'web',
                'permissions' => [
                    'ViewAny:TrainingSession', 'View:TrainingSession',
                    'ViewAny:WorkoutProgram', 'View:WorkoutProgram',
                    'ViewAny:Exercise', 'View:Exercise',
                    'ViewAny:Invoice', 'View:Invoice',
                ],
            ],
        ];

        static::makeRolesWithPermissions((string) json_encode($rolesWithPermissions));
        static::makeDirectPermissions('[]');

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
