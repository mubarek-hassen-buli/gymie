<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property array<string>|null $specialties
 * @property string|null $bio
 * @property int $max_active_clients
 * @property string $commission_type
 * @property string|null $commission_rate
 * @property bool $is_accepting_clients
 * @property-read User $user
 */
class TrainerProfile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'specialties',
        'bio',
        'max_active_clients',
        'commission_type',
        'commission_rate',
        'is_accepting_clients',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'max_active_clients' => 'integer',
            'commission_rate' => 'decimal:2',
            'is_accepting_clients' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Count currently active clients assigned to this trainer.
     */
    public function activeClientsCount(): int
    {
        return Subscription::query()
            ->where('trainer_id', $this->user_id)
            ->where('status', 'ongoing')
            ->distinct('member_id')
            ->count('member_id');
    }

    /**
     * Check if trainer has room to accept a new client.
     */
    public function canAcceptMoreClients(): bool
    {
        if (! $this->is_accepting_clients) {
            return false;
        }

        return $this->activeClientsCount() < $this->max_active_clients;
    }
}
