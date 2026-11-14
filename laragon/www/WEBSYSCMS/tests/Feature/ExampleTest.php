<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_assets_use_https_when_request_is_forwarded_by_render(): void
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])
            ->get('/')
            ->assertOk()
            ->assertSee('href="https://'.$host.'/assets/css/app.css"', false)
            ->assertSee('src="https://'.$host.'/assets/js/app.js"', false);
    }
}
