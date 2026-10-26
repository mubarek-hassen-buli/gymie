<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WorkoutProgram;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkoutProgramPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WorkoutProgram');
    }

    public function view(AuthUser $authUser, WorkoutProgram $workoutProgram): bool
    {
        return $authUser->can('View:WorkoutProgram');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WorkoutProgram');
    }

    public function update(AuthUser $authUser, WorkoutProgram $workoutProgram): bool
    {
        return $authUser->can('Update:WorkoutProgram');
    }

    public function delete(AuthUser $authUser, WorkoutProgram $workoutProgram): bool
    {
        return $authUser->can('Delete:WorkoutProgram');
    }

    public function restore(AuthUser $authUser, WorkoutProgram $workoutProgram): bool
    {
        return $authUser->can('Restore:WorkoutProgram');
    }

    public function forceDelete(AuthUser $authUser, WorkoutProgram $workoutProgram): bool
    {
        return $authUser->can('ForceDelete:WorkoutProgram');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WorkoutProgram');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WorkoutProgram');
    }

    public function replicate(AuthUser $authUser, WorkoutProgram $workoutProgram): bool
    {
        return $authUser->can('Replicate:WorkoutProgram');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WorkoutProgram');
    }

}