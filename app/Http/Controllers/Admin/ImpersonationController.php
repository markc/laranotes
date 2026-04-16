<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if ($admin->id === $user->id) {
            return back()->with('error', 'Cannot impersonate yourself.');
        }

        if ($user->role === Role::Admin) {
            return back()->with('error', 'Cannot impersonate another admin.');
        }

        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Already impersonating someone. Stop the current session first.');
        }

        $eventId = DB::table('impersonation_events')->insertGetId([
            'admin_id' => $admin->id,
            'target_id' => $user->id,
            'ip' => $request->ip(),
            'started_at' => now(),
        ]);

        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonation_event_id', $eventId);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "Now viewing as {$user->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        $eventId = $request->session()->pull('impersonation_event_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        if ($eventId) {
            DB::table('impersonation_events')
                ->where('id', $eventId)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);
        }

        $admin = User::find($impersonatorId);
        if ($admin) {
            Auth::login($admin);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Returned to your admin session.');
    }
}
