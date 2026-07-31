<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteContent;
use App\Models\WebsiteMedia;
use App\Models\WebsiteSetting;
use App\Support\SiteFrontend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsEditAndLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
    }

    private function admin(): User
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'manage_school_settings', 'guard_name' => 'web'],
        );

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('manage_school_settings');

        return $user;
    }

    public function test_site_ui_page_uses_form_inputs_and_not_json_textareas(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user)
            ->get(route('dashboard.settings.global-labels'));

        $response->assertStatus(200);
        $response->assertDontSee('content_json_en');
        $response->assertDontSee('content_json_bn');
        // nav.home input is rendered
        $response->assertSee('name="labels[en][nav][home]"', false);
        $response->assertSee('name="labels[bn][nav][home]"', false);
        // hero_cta_primary is rendered
        $response->assertSee('name="labels[en][home][hero_cta_primary]"', false);
    }

    public function test_site_ui_form_save_persists_grouped_fields_as_nested_tree(): void
    {
        $user = $this->admin();

        $payload = [
            'labels' => [
                'en' => [
                    'nav' => ['home' => 'Home (EN)', 'about' => 'About (EN)'],
                    'home' => ['hero_cta_primary' => 'Apply now', 'hero_headline' => 'Shape the future'],
                ],
                'bn' => [
                    'nav' => ['home' => 'হোম (BN)'],
                    'home' => ['hero_cta_primary' => 'এখনই আবেদন করুন'],
                ],
            ],
        ];

        $this->actingAs($user)
            ->post(route('dashboard.settings.update.global-labels'), $payload)
            ->assertRedirect(route('dashboard.settings.global-labels'));

        $row = WebsiteContent::where('page', 'site-ui')->firstOrFail();

        $this->assertSame('Home (EN)', $row->content_en['nav']['home']);
        $this->assertSame('হোম (BN)', $row->content_bn['nav']['home']);
        $this->assertSame('About (EN)', $row->content_en['nav']['about']);
        $this->assertSame('Apply now', $row->content_en['home']['hero_cta_primary']);
        $this->assertSame('এখনই আবেদন করুন', $row->content_bn['home']['hero_cta_primary']);
        $this->assertSame('Shape the future', $row->content_en['home']['hero_headline']);
    }

    public function test_school_settings_form_accepts_default_locale(): void
    {
        $user = $this->admin();

        // Seed an existing settings row so the controller's firstOrNew finds it
        // and does not need to insert a fresh row with all NOT NULL columns.
        WebsiteSetting::create($this->settings());

        $response = $this->actingAs($user)
            ->post(route('dashboard.settings.update.general'), [
                'default_locale' => 'bn',
            ]);

        $response->assertRedirect(route('dashboard.settings.index'));
        $this->assertSame('bn', WebsiteSetting::first()->default_locale);
    }

    public function test_school_settings_form_rejects_unsupported_locale(): void
    {
        $user = $this->admin();

        WebsiteSetting::create($this->settings());

        $response = $this->actingAs($user)
            ->from(route('dashboard.settings.index'))
            ->post(route('dashboard.settings.update.general'), [
                'default_locale' => 'fr',
            ]);

        $response->assertRedirect(route('dashboard.settings.index'));
        $response->assertSessionHasErrors('default_locale');
    }

    public function test_first_time_visitor_sees_default_locale(): void
    {
        WebsiteSetting::create($this->settings(['default_locale' => 'bn']));

        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertSame('bn', app()->getLocale());
    }

    public function test_user_chosen_locale_takes_precedence_over_default(): void
    {
        WebsiteSetting::create($this->settings(['default_locale' => 'bn']));

        $response = $this->get('/locale/en');
        $response->assertStatus(302);
        $response->assertSessionHas('locale', 'en');

        $this->get('/')->assertStatus(200);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_default_locale_falls_back_to_en_when_unsupported(): void
    {
        WebsiteSetting::create($this->settings(['default_locale' => 'fr']));

        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertSame('en', app()->getLocale());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settings(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'Test School',
            'established_year' => 2000,
            'address' => '1 Test St',
            'city' => 'Testville',
            'state' => 'Test State',
            'country' => 'Bangladesh',
            'postal_code' => '1000',
            'phone' => '+1-555-0100',
            'email' => 'test@school.example',
        ], $overrides);
    }

    public function test_site_ui_overrides_apply_through_merged_helper(): void
    {
        $payload = [
            'labels' => [
                'en' => [
                    'nav' => ['home' => 'Home override'],
                    'home' => ['hero_headline' => 'CMS headline'],
                ],
            ],
        ];

        $this->actingAs($this->admin())
            ->post(route('dashboard.settings.update.global-labels'), $payload)
            ->assertRedirect(route('dashboard.settings.global-labels'));

        $merged = SiteFrontend::merged();
        $this->assertSame('Home override', $merged['nav']['home']);
        $this->assertSame('CMS headline', $merged['home']['hero_headline']);
    }

    public function test_about_page_with_repeater_sections_renders_without_errors(): void
    {
        // About has a 'sections' repeater_sections field. Loading the edit
        // page for it must not raise an "Undefined variable $nameBn" error
        // from the shared _pair-text / _pair-textarea partials.
        $this->actingAs($this->admin())
            ->get(route('dashboard.cms.edit', ['page' => 'about']))
            ->assertStatus(200)
            ->assertSee('__NAME__[__INDEX__][heading_en]', false)
            ->assertDontSee('Add section', false);
    }

    public function test_public_site_has_no_theme_toggle_or_bootstrap(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('data-theme-toggle', false);
        $response->assertDontSee('data-theme-label', false);
        $response->assertDontSee('school-theme', false);
        $response->assertDontSee('localStorage.getItem(\'school-theme\')', false);
    }

    public function test_dashboard_has_no_theme_toggle(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertDontSee('data-theme-toggle', false)
            ->assertDontSee('data-theme-label', false)
            ->assertDontSee('localStorage.getItem(\'school-theme\')', false);
    }

    public function test_public_nav_uses_1367_breakpoint_and_has_sectioned_panel(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Hamburger trigger visible below 1367px, hidden from 1367px up.
        $response->assertSee('data-site-nav-trigger', false);
        $response->assertSee('min-[1367px]:hidden', false);

        // Desktop nav visible from 1367px up.
        $response->assertSee('min-[1367px]:flex', false);

        // Panel uses the same breakpoint + has sectioned content.
        $response->assertSee('id="site-nav-panel"', false);
        $response->assertSee('data-site-nav-panel', false);
        $response->assertSee('Menu', false);
        $response->assertSee('Language', false);
        $response->assertSee('Contact', false);

        // Both menu/close icons present in the trigger.
        $response->assertSee('data-icon-menu', false);
        $response->assertSee('data-icon-close', false);

        // Auto-close wiring present in the inline JS.
        $response->assertSee('data-site-nav-link', false);
    }

    public function test_repeater_sections_save_round_trip(): void
    {
        $payload = [
            'title_en' => 'About',
            'is_active' => 1,
            'intro_en' => 'About intro',
            'sections' => [
                [
                    'heading_en' => 'History',
                    'heading_bn' => 'ইতিহাস',
                    'paragraphs_en' => "Line one\n\nLine two",
                    'paragraphs_bn' => 'লাইন এক',
                ],
            ],
        ];

        $this->actingAs($this->admin())
            ->put(route('dashboard.cms.update', ['page' => 'about']), $payload)
            ->assertRedirect(route('dashboard.cms.edit', ['page' => 'about']));

        $row = WebsiteContent::where('page', 'about')->firstOrFail();
        $this->assertSame('About intro', $row->content_en['intro']);
        $this->assertSame('History', $row->content_en['sections'][0]['heading']);
        $this->assertSame('ইতিহাস', $row->content_bn['sections'][0]['heading']);
        $this->assertSame(['Line one', 'Line two'], $row->content_en['sections'][0]['paragraphs']);
        $this->assertSame(['লাইন এক'], $row->content_bn['sections'][0]['paragraphs']);
    }

    public function test_cms_shared_image_upload_saves_media_and_url(): void
    {
        $file = UploadedFile::fake()->image('hero.jpg', 100, 100);

        $this->actingAs($this->admin())
            ->from(route('dashboard.cms.edit', ['page' => 'home']))
            ->put(route('dashboard.cms.update', ['page' => 'home']), [
                'title_en' => 'Home',
                'is_active' => '1',
                'hero_background_image' => $file,
            ])
            ->assertRedirect(route('dashboard.cms.edit', ['page' => 'home']));

        $row = WebsiteContent::where('page', 'home')->firstOrFail();
        $this->assertStringStartsWith('/storage/media/', $row->content_en['hero']['background_image']);

        $media = WebsiteMedia::firstOrFail();
        $this->assertSame('hero', $media->title);
        $this->assertSame('CMS', $media->category);
    }

    public function test_cms_image_upload_ignores_url_text_field(): void
    {
        $file = UploadedFile::fake()->image('alt.png');

        $this->actingAs($this->admin())
            ->put(route('dashboard.cms.update', ['page' => 'home']), [
                'title_en' => 'Home',
                'is_active' => '1',
                'hero_background_image' => 'https://example.com/old.jpg',
                'hero_background_image_en' => 'https://example.com/en.jpg',
                'hero_background_image_bn' => 'https://example.com/bn.jpg',
                'hero_background_image' => $file,
            ])
            ->assertRedirect(route('dashboard.cms.edit', ['page' => 'home']));

        $row = WebsiteContent::where('page', 'home')->firstOrFail();
        $this->assertStringStartsWith('/storage/media/', $row->content_en['hero']['background_image']);
    }

    public function test_cms_non_shared_image_upload_saves_en_and_bn_trees(): void
    {
        $fileEn = UploadedFile::fake()->image('principal-en.jpg');
        $fileBn = UploadedFile::fake()->image('principal-bn.jpg');

        $this->actingAs($this->admin())
            ->put(route('dashboard.cms.update', ['page' => 'home']), [
                'title_en' => 'Home',
                'is_active' => '1',
                'principal_photo_en' => $fileEn,
                'principal_photo_bn' => $fileBn,
            ])
            ->assertRedirect(route('dashboard.cms.edit', ['page' => 'home']));

        $row = WebsiteContent::where('page', 'home')->firstOrFail();
        $this->assertStringStartsWith('/storage/media/', $row->content_en['principal']['photo']);
        $this->assertStringStartsWith('/storage/media/', $row->content_bn['principal']['photo']);
        $this->assertNotSame($row->content_en['principal']['photo'], $row->content_bn['principal']['photo']);
        $this->assertSame(2, WebsiteMedia::count());
    }
}
