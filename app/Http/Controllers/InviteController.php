<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InviteController extends Controller
{
    private const EXPIRY_DAYS = 14;

    public function index(Request $request): Response
    {
        $actor = $request->user();

        $invites = Invite::with('inviter:id,name')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (Invite $i) => [
                'id' => $i->id,
                'email' => $i->email,
                'role' => $i->role?->value,
                'token' => $i->token,
                'accept_url' => url('/invite/'.$i->token),
                'inviter' => $i->inviter ? ['id' => $i->inviter->id, 'name' => $i->inviter->name] : null,
                'expires_at' => $i->expires_at?->toIso8601String(),
                'accepted_at' => $i->accepted_at?->toIso8601String(),
                'revoked_at' => $i->revoked_at?->toIso8601String(),
                'is_claimable' => $i->isClaimable(),
            ])
            ->all();

        return Inertia::render('invites/index', [
            'invites' => $invites,
            'invitable_roles' => $this->invitableRoles($actor->role),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $invitable = $this->invitableRoles($actor->role);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in($invitable)],
        ]);

        // Second-layer check: enum-level canInvite, in case $invitable ever
        // drifts from the enum's own policy.
        $target = Role::from($data['role']);
        if (! $actor->role->canInvite($target)) {
            throw ValidationException::withMessages([
                'role' => 'You are not allowed to invite this role.',
            ]);
        }

        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A user with that email already exists.',
            ]);
        }

        Invite::create([
            'email' => $data['email'],
            'role' => $target->value,
            'token' => Str::random(64),
            'invited_by' => $actor->id,
            'expires_at' => now()->addDays(self::EXPIRY_DAYS),
        ]);

        return back()->with('success', 'Invite created.');
    }

    public function destroy(Request $request, Invite $invite): RedirectResponse
    {
        $actor = $request->user();

        // Admin can revoke anything; moderator can revoke only their own.
        if (! $actor->role->isAdmin() && $invite->invited_by !== $actor->id) {
            abort(403);
        }

        if ($invite->accepted_at !== null) {
            return back()->with('error', 'Cannot revoke an invite that has already been accepted.');
        }

        $invite->update(['revoked_at' => now()]);

        return back()->with('success', 'Invite revoked.');
    }

    public function show(string $token): Response
    {
        $invite = $this->loadClaimableOrFail($token);

        return Inertia::render('invites/show', [
            'invite' => [
                'token' => $invite->token,
                'email' => $invite->email,
                'role' => $invite->role?->value,
                'expires_at' => $invite->expires_at?->toIso8601String(),
                'inviter' => $invite->inviter?->only(['id', 'name']),
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = $this->loadClaimableOrFail($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (User::where('email', $invite->email)->exists()) {
            $invite->update(['revoked_at' => now()]);
            throw ValidationException::withMessages([
                'email' => 'A user with that email already exists.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $invite->email,
            'password' => Hash::make($data['password']),
            'role' => $invite->role?->value ?? Role::User->value,
            'email_verified_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to Laranotes.');
    }

    private function loadClaimableOrFail(string $token): Invite
    {
        $invite = Invite::with('inviter')->where('token', $token)->first();
        if (! $invite || ! $invite->isClaimable()) {
            throw new HttpException(404, 'Invite not found or no longer claimable.');
        }

        return $invite;
    }

    /**
     * @return array<int, string>
     */
    private function invitableRoles(Role $inviter): array
    {
        return collect(Role::cases())
            ->filter(fn (Role $r) => $inviter->canInvite($r))
            ->map(fn (Role $r) => $r->value)
            ->values()
            ->all();
    }
}
