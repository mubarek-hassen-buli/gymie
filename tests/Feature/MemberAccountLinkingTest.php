<?php

declare(strict_types=1);

use App\Filament\Resources\Members\Actions\CreateMemberAccountAction;
use App\Filament\Resources\Members\Actions\UnlinkMemberAccountAction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('allows front_desk and super_admin to view create account action only on unlinked members', function (): void {
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $unlinkedMember = Member::factory()->create(['user_id' => null]);
    $linkedMember = Member::factory()->create(['user_id' => User::factory()->create()->id]);

    $createAction = CreateMemberAccountAction::make();
    $unlinkAction = UnlinkMemberAccountAction::make();

    // 1. Front Desk
    $this->actingAs($frontDesk);
    expect($createAction->record($unlinkedMember)->isVisible())->toBeTrue();
    expect($createAction->record($linkedMember)->isVisible())->toBeFalse();
    expect($unlinkAction->record($unlinkedMember)->isVisible())->toBeFalse();
    expect($unlinkAction->record($linkedMember)->isVisible())->toBeTrue();

    // 2. Super Admin
    $this->actingAs($superAdmin);
    expect($createAction->record($unlinkedMember)->isVisible())->toBeTrue();
    expect($createAction->record($linkedMember)->isVisible())->toBeFalse();
    expect($unlinkAction->record($unlinkedMember)->isVisible())->toBeFalse();
    expect($unlinkAction->record($linkedMember)->isVisible())->toBeTrue();

    // 3. Trainer (must be hidden)
    $this->actingAs($trainer);
    expect($createAction->record($unlinkedMember)->isVisible())->toBeFalse();
    expect($createAction->record($linkedMember)->isVisible())->toBeFalse();
    expect($unlinkAction->record($unlinkedMember)->isVisible())->toBeFalse();
    expect($unlinkAction->record($linkedMember)->isVisible())->toBeFalse();
});

it('provisions a user account with member role and links it to the member', function (): void {
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');
    $this->actingAs($frontDesk);

    $member = Member::factory()->create([
        'name' => 'Alice Athlete',
        'email' => 'alice@example.com',
        'user_id' => null,
    ]);

    $action = CreateMemberAccountAction::make()->record($member);

    // Execute the action with form payload
    $action->call([
        'data' => [
            'name' => 'Alice Athlete',
            'email' => 'alice@example.com',
            'password' => 'SecurePass123!',
        ],
    ]);

    $member->refresh();

    expect($member->user_id)->not->toBeNull();

    $user = User::find($member->user_id);
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Alice Athlete');
    expect($user->email)->toBe('alice@example.com');
    expect(Hash::check('SecurePass123!', $user->password))->toBeTrue();
    expect($user->hasRole('member'))->toBeTrue();
    expect($user->isMember())->toBeTrue();

    // Assert authentication works
    $authSuccess = Auth::attempt([
        'email' => 'alice@example.com',
        'password' => 'SecurePass123!',
    ]);
    expect($authSuccess)->toBeTrue();
});

it('unlinks and deletes the member portal login when disabled', function (): void {
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');
    $this->actingAs($frontDesk);

    $user = User::factory()->create([
        'email' => 'bob@example.com',
    ]);
    $user->assignRole('member');

    $member = Member::factory()->create([
        'name' => 'Bob Builder',
        'user_id' => $user->id,
    ]);

    $unlinkAction = UnlinkMemberAccountAction::make()->record($member);

    $unlinkAction->call();

    $member->refresh();

    expect($member->user_id)->toBeNull();
    expect(User::find($user->id))->toBeNull();
});
