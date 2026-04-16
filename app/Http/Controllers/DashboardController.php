<?php

namespace App\Http\Controllers;

use App\Repositories\NoteRepositoryInterface;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private NoteRepositoryInterface $notes,
        private SettingService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $limit = $this->settings->get('notes_per_page');

        return Inertia::render('dashboard', [
            'recent_notes' => $this->notes->recentForUser($user, $limit),
        ]);
    }
}
