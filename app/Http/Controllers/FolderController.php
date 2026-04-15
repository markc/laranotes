<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        Folder::create($data);

        return back()->with('success', 'Folder created.');
    }

    public function show(Folder $folder): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function edit(Folder $folder): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateFolderRequest $request, Folder $folder): RedirectResponse
    {
        $this->authorize('update', $folder);
        $folder->update($request->validated());

        return back()->with('success', 'Folder updated.');
    }

    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        if ($folder->notes()->exists() || $folder->children()->exists()) {
            return back()->with('error', 'Folder is not empty.');
        }

        $folder->delete();

        return back()->with('success', 'Folder deleted.');
    }

    public function tree(Request $request)
    {
        return response()->json(Folder::tree($request->user()));
    }
}
