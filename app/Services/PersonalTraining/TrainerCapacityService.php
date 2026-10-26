<?php

namespace App\Services\PersonalTraining;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Domain service managing personal trainer capacity and roster limits.
 */
class TrainerCapacityService
{
    /**
     * Check if a trainer can accept an additional active client.
     */
    public function canAcceptClient(User $trainer): bool
    {
        $profile = $trainer->trainerProfile;

        if (! $profile) {
            return true;
        }

        return $profile->canAcceptMoreClients();
    }

    /**
     * Assert that a trainer has capacity, throwing a validation exception if full.
     *
     * @throws ValidationException
     */
    public function assertHasCapacity(User $trainer): void
    {
        $profile = $trainer->trainerProfile;

        if ($profile && ! $profile->canAcceptMoreClients()) {
            throw ValidationException::withMessages([
                'trainer_id' => [
                    __('Trainer :name has reached their maximum capacity of :max active clients.', [
                        'name' => $trainer->name,
                        'max' => $profile->max_active_clients,
                    ]),
                ],
            ]);
        }
    }

    /**
     * Get all active trainers who currently have capacity to take new clients.
     *
     * @return Collection<int, User>
     */
    public function getAvailableTrainers(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'trainer'))
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereDoesntHave('trainerProfile')
                    ->orWhereHas('trainerProfile', function ($profileQuery) {
                        $profileQuery->where('is_accepting_clients', true);
                    });
            })
            ->get()
            ->filter(fn (User $trainer): bool => $this->canAcceptClient($trainer))
            ->values();
    }

    /**
     * Get workload metrics for a trainer.
     *
     * @return array{active_clients: int, max_clients: int, utilization_percentage: float}
     */
    public function getWorkload(User $trainer): array
    {
        $profile = $trainer->trainerProfile;
        $max = $profile ? $profile->max_active_clients : 10;
        $active = $profile ? $profile->activeClientsCount() : $trainer->assignedSubscriptions()
            ->where('status', 'ongoing')
            ->distinct('member_id')
            ->count('member_id');

        $utilization = $max > 0 ? round(($active / $max) * 100, 1) : 0.0;

        return [
            'active_clients' => $active,
            'max_clients' => $max,
            'utilization_percentage' => min($utilization, 100.0),
        ];
    }
}
