<?php

namespace Tests\Feature;

use App\Models\AdmissionSetting;
use App\Models\Event;
use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomeHeroAndSliderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'web');
    }

    private function admin(): User
    {
        Permission::findOrCreate('manage_school_settings', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('manage_school_settings');

        return $user;
    }

    private function updateHome(array $payload): void
    {
        $this->actingAs($this->admin())
            ->put(route('dashboard.cms.update', ['page' => 'home']), array_merge([
                'title_en' => 'Home',
                'is_active' => '1',
            ], $payload))
            ->assertRedirect(route('dashboard.cms.edit', ['page' => 'home']));
    }

    public function test_cms_editor_renders_hero_design_select_and_slider_fields(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.cms.edit', ['page' => 'home']));

        $response->assertStatus(200);
        $response->assertSee('name="hero_design"', false);
        $response->assertSee('Design 2 — Centered banner', false);
        $response->assertSee('Design 3 — Light split with photo', false);
        $response->assertSee('Design 4 — Minimal gradient', false);
        $response->assertSee('Design 5 — Full-width image with school name', false);
        $response->assertSee('Design 6 — School name with hero slider', false);
        $response->assertSee('data-cms-repeater', false);
        $response->assertSee('Photo slider', false);
    }

    public function test_hero_design_selection_persists(): void
    {
        $this->updateHome(['hero_design' => 'design-3']);

        $row = WebsiteContent::where('page', 'home')->firstOrFail();
        $this->assertSame('design-3', $row->content_en['hero_design']);
    }

    public function test_homepage_renders_selected_hero_design(): void
    {
        $this->updateHome(['hero_design' => 'design-2']);

        $response = $this->get('/');
        $response->assertStatus(200);

        // design-2 centered banner: school name pill + centered layout classes
        $response->assertSee('bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-900', false);
        $response->assertSee('text-center', false);
    }

    public function test_homepage_defaults_to_design_1(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // design-1 dark split hero markers
        $response->assertSee('from-slate-900 via-slate-800 to-indigo-950', false);
        $response->assertSee('data-teachers-slider', false);
    }

    public function test_slider_save_round_trip_with_image_upload(): void
    {
        $file = UploadedFile::fake()->image('event.jpg', 200, 200);

        $this->actingAs($this->admin())
            ->from(route('dashboard.cms.edit', ['page' => 'home']))
            ->put(route('dashboard.cms.update', ['page' => 'home']), [
                'title_en' => 'Home',
                'is_active' => '1',
                'slider' => [
                    [
                        'image' => $file,
                        'title_en' => 'Annual sports day',
                        'title_bn' => 'বার্ষিক ক্রীড়া দিবস',
                        'caption_en' => 'Students competing',
                        'link_en' => 'https://example.com/events',
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard.cms.edit', ['page' => 'home']));

        $row = WebsiteContent::where('page', 'home')->firstOrFail();
        $this->assertStringStartsWith('/storage/media/', $row->content_en['slider'][0]['image']);
        $this->assertSame('Annual sports day', $row->content_en['slider'][0]['title']);
        $this->assertSame('বার্ষিক ক্রীড়া দিবস', $row->content_bn['slider'][0]['title']);
        $this->assertSame('Students competing', $row->content_en['slider'][0]['caption']);
        $this->assertSame('https://example.com/events', $row->content_en['slider'][0]['link']);
    }

    public function test_homepage_renders_cms_slider_slides(): void
    {
        $this->updateHome([
            'slider' => [
                [
                    'image' => '/storage/media/slide-1.jpg',
                    'title_en' => 'Science fair 2026',
                    'caption_en' => 'Winners announced',
                    'link_en' => 'https://example.com/news',
                ],
                [
                    'image' => '/storage/media/slide-2.jpg',
                    'title_en' => 'Cultural program',
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Science fair 2026', false);
        $response->assertSee('Cultural program', false);
        $response->assertSee('/storage/media/slide-1.jpg', false);
        $response->assertSee('data-slider-carousel', false);
        $response->assertSee('Recent events', false);
    }

    public function test_hero_shows_admission_button_when_admissions_open(): void
    {
        AdmissionSetting::getSettings()->update(['is_open' => true]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Apply for admission', false);
    }

    public function test_hero_hides_admission_button_and_shows_contact_when_closed(): void
    {
        AdmissionSetting::getSettings()->update(['is_open' => false]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('Apply for admission', false);
        $response->assertSee(route('site.contact'), false);
        $response->assertSee('Contact us', false);
    }

    public function test_uploaded_principal_photo_renders_on_homepage(): void
    {
        $this->updateHome([
            'principal_section_title_en' => "Principal's Message",
            'principal_photo_en' => '/storage/media/principal.jpg',
            'principal_name_en' => 'Dr. Amina Rahman',
            'principal_designation_en' => 'Principal',
            'principal_message_en' => 'Welcome to our school.',
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('/storage/media/principal.jpg', false);
        $response->assertSee('Dr. Amina Rahman', false);
    }

    public function test_about_page_contains_ministry_guidelines(): void
    {
        $about = WebsiteContent::updateOrCreate(
            ['page' => 'about'],
            [
                'is_active' => true,
                'title_en' => 'About Us',
                'content_en' => [
                    'sections' => [
                        ['heading' => 'Education ministry website guidelines', 'paragraphs' => ['DSHE directive.'], 'bullets' => ['Institution profile and identity', 'Managing committee information']],
                    ],
                ],
            ]
        );

        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertSee('Education ministry website guidelines', false);
        $response->assertSee('Managing committee information', false);
        $response->assertDontSee('About this website software', false);
    }

    public function test_homepage_slider_renders_above_upcoming_events(): void
    {
        Event::create([
            'title' => 'Future Event',
            'status' => 'published',
            'start_date' => now()->addDays(5),
            'created_by' => $this->admin()->id,
        ]);

        $this->updateHome(['slider' => [
            ['image' => 'https://example.com/slide1.jpg', 'title_en' => 'Slide One', 'caption_en' => '', 'link_en' => ''],
        ]]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Recent events &amp; activities', false);
        $response->assertSee('Slide One', false);
        $response->assertSee('Upcoming events', false);
        $response->assertDontSee('Recent Events and Activities', false);

        $posSlider = strpos($response->getContent(), 'Slide One');
        $posEvents = strpos($response->getContent(), 'Upcoming events');
        $this->assertTrue($posSlider !== false && $posEvents !== false && $posSlider < $posEvents);
    }

    public function test_homepage_renders_design_5_full_width_hero(): void
    {
        $this->updateHome(['hero_design' => 'design-5']);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('min-h-[85vh]', false);
    }

    public function test_dashboard_about_page_renders(): void
    {
        $sections = [['heading' => 'History', 'paragraphs' => ['Founded in 2000.']]];
        WebsiteContent::updateOrCreate(
            ['page' => 'about'],
            [
                'is_active' => true,
                'title_en' => 'About Us',
                'content_en' => ['intro' => 'Welcome', 'sections' => $sections],
                'content_bn' => ['intro' => 'Welcome'],
            ]
        );

        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.settings.about'));

        $response->assertStatus(200);
        $response->assertSee('About Page', false);
        $response->assertSee('Welcome', false);
        $response->assertSee('History', false);
    }

    public function test_dashboard_about_page_save(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.settings.update.about'), [
                'title_en' => 'About Our School',
                'intro_en' => 'School introduction text.',
                'sections' => [
                    ['heading_en' => 'Vision', 'paragraphs_en' => 'Our vision is excellence.'],
                ],
                'is_active' => true,
            ]);

        $content = WebsiteContent::where('page', 'about')->first();
        $this->assertNotNull($content);
        $this->assertSame('About Our School', $content->title_en);
        $this->assertSame('School introduction text.', $content->content_en['intro']);
        $this->assertSame('Vision', $content->content_en['sections'][0]['heading']);
    }

    public function test_media_picker_select_mode_renders_standalone_layout(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.media.index', ['select' => '1']));

        $response->assertStatus(200);
        $response->assertSee('Media Library', false);
        $response->assertSee('Click an image to insert it into the page.', false);
        $response->assertDontSee('admin-shell', false);
        $response->assertDontSee('Upload media', false);
    }
}
