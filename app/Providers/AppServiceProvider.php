<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerSqliteCompatibilityFunctions();

        View::composer('*', function ($view) {
            $view->with('errors', session('_form_errors', []));
        });
    }

    private function registerSqliteCompatibilityFunctions(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');
        if ($database !== ':memory:' && ! is_file((string) $database)) {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('NOW', static fn (): string => now()->toDateTimeString(), 0);
        $pdo->sqliteCreateFunction('CURDATE', static fn (): string => date('Y-m-d'), 0);
        $pdo->sqliteCreateFunction('YEAR', static function ($value): ?int {
            $timestamp = strtotime((string) $value);

            return $timestamp === false ? null : (int) date('Y', $timestamp);
        }, 1);
        $pdo->sqliteCreateFunction('DATE_FORMAT', static function ($value, $format): ?string {
            $timestamp = strtotime((string) $value);
            if ($timestamp === false) {
                return null;
            }

            $format = strtr((string) $format, [
                '%Y' => 'Y',
                '%y' => 'y',
                '%m' => 'm',
                '%d' => 'd',
                '%H' => 'H',
                '%i' => 'i',
                '%s' => 's',
            ]);

            return date($format, $timestamp);
        }, 2);
        $pdo->sqliteCreateFunction('FIELD', static function ($value, ...$options): int {
            foreach ($options as $index => $option) {
                if ($value == $option) {
                    return $index + 1;
                }
            }

            return 0;
        }, -1);
        $pdo->sqliteCreateFunction('IF', static fn ($condition, $whenTrue, $whenFalse) => $condition ? $whenTrue : $whenFalse, 3);
    }
}
