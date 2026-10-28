<?php

declare(strict_types=1);

namespace App\Filament\Resources\Members\Actions;

use App\Models\Member;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Action to disable and unlink a member's portal login account.
 */
class UnlinkMemberAccountAction
{
    public static function make(string $name = 'unlink_member_login'): Action
    {
        return Action::make($name)
            ->label('Disable Portal Login')
            ->icon('heroicon-m-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (?Member $record): string => $record ? "Disable Portal Login for {$record->name}" : 'Disable Portal Login')
            ->modalDescription('Are you sure you want to disable and unlink this member\'s portal login? The member will immediately lose access to the portal.')
            ->modalSubmitActionLabel('Disable & Unlink')
            ->visible(function (?Member $record): bool {
                if (! $record) {
                    return false;
                }

                $user = auth()->user();

                if (! $user || (! $user->isSuperAdmin() && ! $user->isFrontDesk())) {
                    return false;
                }

                return $record->user_id !== null;
            })
            ->action(function (?Member $record): void {
                if (! $record) {
                    return;
                }

                DB::transaction(function () use ($record): void {
                    $userId = $record->user_id;
                    $record->update(['user_id' => null]);

                    if ($userId) {
                        $user = User::find($userId);
                        if ($user && $user->hasRole('member')) {
                            $user->delete();
                        }
                    }
                });

                Notification::make()
                    ->title('Portal Login Disabled')
                    ->body("Portal login for {$record->name} has been disabled.")
                    ->warning()
                    ->send();
            });
    }
}
