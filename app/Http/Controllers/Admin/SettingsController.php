<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function index(): Response
    {
        return Inertia::render('admin/settings/index', [
            'settings' => $this->settings->grouped(),
            'registry' => $this->settings->registry(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $registry = $this->settings->registry();
        $rules = [];

        foreach ($registry as $key => $meta) {
            $rules[$key] = match ($meta['type']) {
                'bool' => ['sometimes', 'boolean'],
                'int' => ['sometimes', 'integer', 'min:1'],
                'enum' => ['sometimes', 'string', Rule::in($meta['options'] ?? [])],
                default => ['sometimes', 'string', 'max:500'],
            };
        }

        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        $this->settings->flush();

        return back()->with('success', 'Settings saved.');
    }
}
