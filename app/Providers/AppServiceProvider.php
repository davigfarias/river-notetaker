<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAccessTokenIsValid;
use App\Support\Exercises\CreateExampleStrategy;
use App\Support\Exercises\ExerciseStrategies;
use App\Support\Exercises\RecallStatementToTitleStrategy;
use App\Support\Exercises\RecallTitleToStatementStrategy;
use App\Support\Markdown\HeadingIdsExtension;
use App\Support\Sm2Scheduler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Highlight\HighlightExtension;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExerciseStrategies::class, fn (): ExerciseStrategies => new ExerciseStrategies([
            new RecallTitleToStatementStrategy,
            new RecallStatementToTitleStrategy,
            new CreateExampleStrategy,
        ]));

        $this->app->singleton(Sm2Scheduler::class, fn (): Sm2Scheduler => new Sm2Scheduler(
            initialEase: (float) config('principios.scheduler.initial_ease'),
            minEase: (float) config('principios.scheduler.min_ease'),
            maxIntervalDays: (int) config('principios.scheduler.max_interval_days'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Livewire::addPersistentMiddleware([
            EnsureAccessTokenIsValid::class,
        ]);

        Str::macro('markdownRich', fn (string $string, array $options = [], ?string $headingIdPrefix = null): string => Str::markdown($string, $options, [
            new FootnoteExtension,
            new HighlightExtension,
            ...($headingIdPrefix !== null ? [new HeadingIdsExtension($headingIdPrefix)] : []),
        ]));
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
