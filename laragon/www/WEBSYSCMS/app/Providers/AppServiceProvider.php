<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('gmail-api', function (array $config = []) {
            foreach (['client_id', 'client_secret', 'refresh_token'] as $key) {
                if (empty($config[$key])) {
                    throw new RuntimeException('The Gmail API mailer is missing '.$key.'.');
                }
            }

            return new GmailApiTransport(
                $config['client_id'],
                $config['client_secret'],
                $config['refresh_token'],
            );
        });

        View::composer('*', function ($view) {
            $view->with('errors', session('_form_errors', []));
        });
    }
}
