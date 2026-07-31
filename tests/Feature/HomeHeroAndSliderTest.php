<?php

namespace Tests\Feature;

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
}
