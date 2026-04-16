<?php

namespace App\Http\Middleware;

use App\Models\Note;
use App\Models\User;
use App\Repositories\FolderRepositoryInterface;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $impersonatorId = $request->session()->get('impersonator_id');
        $impersonator = $impersonatorId ? User::find($impersonatorId) : null;

        $settings = app(SettingService::class);

        return [
            ...parent::share($request),
            'name' => $settings->get('site_title'),
            'siteDescription' => $settings->get('site_description'),
            'defaultEditor' => $settings->get('default_editor'),
            'defaultTheme' => $settings->get('default_theme'),
            'defaultScheme' => $settings->get('default_scheme'),
            'auth' => [
                'user' => $user,
                'role' => $user?->role?->value,
                // canHints is advisory — authoritative checks remain in policies.
                // Use these to hide UI affordances, never to gate server state.
                'canHints' => $user ? $this->canHints($user) : null,
                'impersonator' => $impersonator ? [
                    'id' => $impersonator->id,
                    'name' => $impersonator->name,
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'folderTree' => fn () => $user ? app(FolderRepositoryInterface::class)->tree($user) : [],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function canHints(User $user): array
    {
        return [
            'createNotes' => $user->can('create', Note::class),
            'createFolders' => $user->can('create', Folder::class),
            'moderate' => $user->role?->canModerate() ?? false,
            'manageUsers' => $user->role?->isAdmin() ?? false,
        ];
    }
}
