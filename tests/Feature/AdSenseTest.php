<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdSenseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_ads_are_loaded_on_public_pages_but_not_authentication_pages(): void
    {
        config([
            'adsense.enabled' => true,
            'adsense.publisher_id' => 'ca-pub-1234567890123456',
            'adsense.public_slot_id' => '1234567890',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js')
            ->assertSee('data-ad-slot="1234567890"', false)
            ->assertSee('Advertisement');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js')
            ->assertDontSee('data-ad-slot="1234567890"', false);
    }

    public function test_ads_are_not_loaded_when_disabled_or_missing_ad_unit_configuration(): void
    {
        config([
            'adsense.enabled' => true,
            'adsense.publisher_id' => 'ca-pub-1234567890123456',
            'adsense.public_slot_id' => '',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js')
            ->assertDontSee('adsbygoogle');

        config(['adsense.enabled' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');
    }
}
