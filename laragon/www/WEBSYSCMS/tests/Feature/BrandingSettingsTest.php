<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_customize_theme_and_upload_a_site_logo(): void
    {
        $admin = $this->createAdmin();
        $logoPath = null;

        try {
            $this->actingAs($admin)
                ->post('/admin/settings', [
                    'site_name' => 'FixitNow',
                    'site_tagline' => 'Appliance repair, organized.',
                    'contact_email' => 'support@example.com',
                    'brand_color' => '#123abc',
                    'accent_color' => '#45def0',
                    'site_font' => 'georgia',
                    'site_logo' => UploadedFile::fake()->image('logo.png'),
                ])
                ->assertRedirect('/admin/settings');

            $this->assertDatabaseHas('settings', [
                'setting_key' => 'brand_color',
                'setting_value' => '#123abc',
            ]);
            $this->assertDatabaseHas('settings', [
                'setting_key' => 'accent_color',
                'setting_value' => '#45def0',
            ]);
            $this->assertDatabaseHas('settings', [
                'setting_key' => 'site_font',
                'setting_value' => 'georgia',
            ]);

            $logoPath = (string) DB::table('settings')
                ->where('setting_key', 'site_logo')
                ->value('setting_value');

            $this->assertMatchesRegularExpression('/\Auploads\/branding\/[a-f0-9]{32}\.png\z/', $logoPath);
            $this->assertFileExists(public_path($logoPath));

            $this->get('/')
                ->assertOk()
                ->assertSee('--brand: #123abc')
                ->assertSee('--accent: #45def0')
                ->assertSee('--font: Georgia, "Times New Roman", serif', false)
                ->assertSee(asset($logoPath));

            $this->get('/admin/settings')
                ->assertOk()
                ->assertSee('--brand: #123abc')
                ->assertSee(asset($logoPath));
        } finally {
            if ($logoPath !== null && $logoPath !== '') {
                File::delete(public_path($logoPath));
            }
        }
    }

    public function test_admin_cannot_save_an_invalid_theme_color(): void
    {
        $this->actingAs($this->createAdmin())
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'site_name' => 'FixitNow',
                'contact_email' => 'support@example.com',
                'brand_color' => 'red; background:url(javascript:alert(1))',
                'accent_color' => '#45def0',
                'site_font' => 'system',
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('_form_errors.brand_color');

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'brand_color',
        ]);
    }

    public function test_guest_layout_uses_the_saved_theme(): void
    {
        DB::table('settings')->updateOrInsert(
            ['setting_key' => 'brand_color'],
            ['setting_value' => '#123abc']
        );
        DB::table('settings')->updateOrInsert(
            ['setting_key' => 'accent_color'],
            ['setting_value' => '#45def0']
        );
        settings(true);

        $this->get('/login')
            ->assertOk()
            ->assertSee('--brand: #123abc')
            ->assertSee('--accent: #45def0');
    }

    public function test_public_pages_show_the_updated_contact_details_and_service_hours(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('0926 701 4324')
            ->assertSee('fixflow.cms@gmail.com')
            ->assertSee('Pagkakaisa, Naujan, Oriental Mindoro')
            ->assertSee('Daily: 8:00 AM - 8:00 PM');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Serving Naujan and nearby areas in Oriental Mindoro.')
            ->assertDontSee('Metro Manila');
    }

    private function createAdmin(): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Site Admin',
            'email' => 'site-admin@example.com',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }
}
