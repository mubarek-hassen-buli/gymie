<?php

declare(strict_types=1);

namespace App\Filament\Resources\Members\Actions;

use App\Models\Member;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Action to provision a member portal login account for a gym member.
 */
class CreateMemberAccountAction
{
    public static function make(string $name = 'create_member_login'): Action
    {
        return Action::make($name)
            ->label('Create Portal Login')
            ->icon('heroicon-m-user-plus')
            ->color('primary')
            ->visible(function (?Member $record): bool {
                if (! $record) {
                    return false;
                }

                $user = auth()->user();

                if (! $user || (! $user->isSuperAdmin() && ! $user->isFrontDesk())) {
                    return false;
                }

                return $record->user_id === null;
            })
            ->modalHeading(fn (?Member $record): string => $record ? "Create Portal Login for {$record->name}" : 'Create Portal Login')
            ->modalDescription('Provision a member portal user account so this client can view their workouts, sessions, and invoices.')
            ->modalSubmitActionLabel('Create Account')
            ->modalWidth('md')
            ->schema([
                TextInput::make('name')
                    ->label(__('app.fields.name'))
                    ->default(fn (?Member $record): ?string => $record?->name)
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('app.fields.email'))
                    ->email()
                    ->default(fn (?Member $record): ?string => $record?->email)
                    ->required()
                    ->unique(User::class, 'email')
                    ->maxLength(255),

                TextInput::make('password')
                    ->label(__('app.fields.password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(8)),
            ])
            ->action(function (?Member $record, array $data): void {
                if (! $record) {
                    return;
                }

                $name = (string) ($data['name'] ?? $record->name);
                $email = (string) ($data['email'] ?? $record->email);
                $password = (string) ($data['password'] ?? '');

                if ($password === '') {
                    return;
                }

                DB::transaction(function () use ($record, $name, $email, $password): void {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'status' => 'active',
                    ]);

                    $user->assignRole('member');

                    $record->update(['user_id' => $user->id]);
                });

                Notification::make()
                    ->title('Portal Account Created')
                    ->body("User account for {$record->name} was successfully created.")
                    ->success()
                    ->send();
            });
    }
}
