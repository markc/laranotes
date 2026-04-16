<?php

namespace App\Providers;

use App\Repositories\EloquentFolderRepository;
use App\Repositories\EloquentNoteRepository;
use App\Repositories\FolderRepositoryInterface;
use App\Repositories\NoteRepositoryInterface;
use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingService::class);

        $this->app->singleton(NoteRepositoryInterface::class, function ($app) {
            return match ($app->make(SettingService::class)->get('notes_storage')) {
                'flatfile' => $app->make(\App\Repositories\FlatFileNoteRepository::class),
                default => $app->make(EloquentNoteRepository::class),
            };
        });

        $this->app->singleton(FolderRepositoryInterface::class, function ($app) {
            return match ($app->make(SettingService::class)->get('notes_storage')) {
                'flatfile' => $app->make(\App\Repositories\FlatFileFolderRepository::class),
                default => $app->make(EloquentFolderRepository::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRouteBindings();
    }

    protected function configureRouteBindings(): void
    {
        Route::bind('note', function (string $value) {
            $settings = app(SettingService::class);

            if ($settings->get('notes_storage') === 'flatfile') {
                $repo = app(NoteRepositoryInterface::class);
                $data = $repo->findBySlug($value) ?? $repo->find($value);

                if (! $data) {
                    abort(404);
                }

                $note = new \App\Models\Note($data);
                $note->exists = true;

                return $note;
            }

            return \App\Models\Note::where('slug', $value)
                ->orWhere('id', $value)
                ->firstOrFail();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
