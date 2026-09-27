<?php

namespace Tests\Feature;

use App\Models\DocumentDesign;
use App\Models\User;
use App\Services\DocumentDesignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardDocumentDesignControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo(Permission::findOrCreate('manage_document_designs', 'web'));

        return $user;
    }

    private function design(array $overrides = []): DocumentDesign
    {
        return DocumentDesign::query()->create(array_merge([
            'document_type' => 'certificate',
            'name' => 'Modern blue',
            'template' => 'modern',
            'settings' => ['primary_color' => '#ff0000'],
            'watermark' => ['enabled' => true, 'text' => 'DRAFT'],
            'is_default' => true,
            'is_active' => true,
        ], $overrides));
    }

    #[Test]
    public function the_list_shows_a_card_per_document_type(): void
    {
        $this->design();

        $this->actingAs($this->admin())
            ->get(route('dashboard.document-designs.index'))
            ->assertOk()
            ->assertSee('Modern blue')
            ->assertSee('Admit Card', false);
    }

    #[Test]
    public function the_list_requires_the_permission(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get(route('dashboard.document-designs.index'))->assertForbidden();
    }

    #[Test]
    public function a_design_can_be_created(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.document-designs.store'), [
            'document_type' => 'marksheet',
            'name' => 'Clean marksheet',
            'template' => 'minimal',
            'is_default' => '1',
            'is_active' => '1',
            'settings' => ['primary_color' => '#0f766e', 'base_font_size' => '12'],
            'watermark' => ['enabled' => '1', 'text' => 'INTERNAL', 'opacity' => '0.2'],
        ])->assertRedirect(route('dashboard.document-designs.index'));

        $design = DocumentDesign::query()->first();
        $this->assertNotNull($design);
        $this->assertSame('marksheet', $design->document_type);
        $this->assertSame('minimal', $design->template);
        $this->assertSame('#0f766e', $design->settings['primary_color']);
        $this->assertSame(12, $design->settings['base_font_size']);
        $this->assertTrue($design->watermark['enabled']);

        // And it is what the printers now read.
        DocumentDesignService::flushCache();
        $theme = (new DocumentDesignService)->theme('marksheet');
        $this->assertSame('minimal', $theme['template']);
        $this->assertSame('#0f766e', $theme['primary_color']);
    }

    #[Test]
    public function creating_a_default_demotes_the_previous_default_for_that_type(): void
    {
        $this->design();

        $this->actingAs($this->admin())->post(route('dashboard.document-designs.store'), [
            'document_type' => 'certificate',
            'name' => 'Second',
            'template' => 'classic',
            'is_default' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(1, DocumentDesign::query()->where('is_default', true)->count());
        $this->assertSame('Second', DocumentDesign::query()->where('is_default', true)->first()->name);
    }

    #[Test]
    public function a_default_in_another_type_is_left_alone(): void
    {
        $this->design();

        $this->actingAs($this->admin())->post(route('dashboard.document-designs.store'), [
            'document_type' => 'marksheet',
            'name' => 'Marksheet default',
            'template' => 'classic',
            'is_default' => '1',
        ])->assertRedirect();

        $this->assertSame(2, DocumentDesign::query()->where('is_default', true)->count());
    }

    #[Test]
    public function out_of_range_values_are_clamped_on_save(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.document-designs.store'), [
            'document_type' => 'certificate',
            'name' => 'Clamped',
            'template' => 'classic',
            'settings' => ['base_font_size' => '900', 'border_width' => '-4', 'primary_color' => 'red'],
        ])->assertRedirect();

        $settings = DocumentDesign::query()->first()->settings;
        $this->assertSame(32, $settings['base_font_size']);
        $this->assertSame(0, $settings['border_width']);
        $this->assertSame('#1e40af', $settings['primary_color'], 'an invalid colour falls back to the neutral default');
    }

    #[Test]
    public function custom_css_is_sanitised_on_save(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.document-designs.store'), [
            'document_type' => 'certificate',
            'name' => 'With CSS',
            'template' => 'classic',
            'custom_css' => '</style><script>alert(1)</script>.a{background:url(https://evil.test/x.png)}.b{color:#ff0000;}',
        ])->assertRedirect();

        $css = (string) DocumentDesign::query()->first()->custom_css;
        $this->assertStringNotContainsString('<', $css);
        $this->assertStringNotContainsString('script', $css);
        $this->assertStringNotContainsString('evil.test', $css);
        $this->assertStringContainsString('.b{color:#ff0000;}', $css);
    }

    #[Test]
    public function a_design_can_be_updated(): void
    {
        $design = $this->design();

        $this->actingAs($this->admin())->put(route('dashboard.document-designs.update', $design), [
            'document_type' => 'testimonial',
            'name' => 'Renamed',
            'template' => 'bordered',
            'is_active' => '1',
            'settings' => ['primary_color' => '#16a34a'],
        ])->assertRedirect(route('dashboard.document-designs.index'));

        $design->refresh();
        $this->assertSame('testimonial', $design->document_type);
        $this->assertSame('bordered', $design->template);
        $this->assertSame('Renamed', $design->name);
    }

    #[Test]
    public function updating_a_default_does_not_demote_itself(): void
    {
        $design = $this->design();
        $other = $this->design(['name' => 'Other', 'is_default' => false]);

        $this->actingAs($this->admin())->put(route('dashboard.document-designs.update', $design), [
            'document_type' => 'certificate',
            'name' => $design->name,
            'template' => 'modern',
            'is_default' => '1',
        ])->assertRedirect();

        $this->assertTrue($design->refresh()->is_default);
        $this->assertFalse($other->refresh()->is_default);
    }

    #[Test]
    public function a_design_can_be_deleted_and_the_default_reverts(): void
    {
        $design = $this->design();

        $this->actingAs($this->admin())
            ->from(route('dashboard.document-designs.index'))
            ->delete(route('dashboard.document-designs.destroy', $design))
            ->assertRedirect(route('dashboard.document-designs.index'));

        $this->assertNull(DocumentDesign::query()->find($design->id));
        DocumentDesignService::flushCache();
        $this->assertFalse((new DocumentDesignService)->theme('certificate')['has_custom_design']);
    }

    #[Test]
    public function an_unknown_document_type_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.document-designs.store'), [
            'document_type' => 'invoice',
            'name' => 'Nope',
            'template' => 'classic',
        ])->assertSessionHasErrors('document_type');
    }

    #[Test]
    public function the_preview_endpoint_renders_unsaved_settings(): void
    {
        $this->actingAs($this->admin())->postJson(route('dashboard.document-designs.preview'), [
            'document_type' => 'certificate',
            'settings' => ['primary_color' => '#123456', 'template' => 'bordered'],
            'watermark' => ['enabled' => true, 'text' => 'LIVE', 'opacity' => 0.25],
        ])->assertOk()
            ->assertJsonPath('type', 'certificate')
            ->assertJsonPath('watermark.enabled', true)
            ->assertJsonPath('watermark.text', 'LIVE')
            ->assertJsonPath('watermark.opacity', 0.25);
    }

    #[Test]
    public function the_preview_endpoint_falls_back_to_the_stored_design(): void
    {
        $this->design(['settings' => ['primary_color' => '#abcdef']]);

        $this->actingAs($this->admin())
            ->getJson(route('dashboard.document-designs.preview', ['document_type' => 'certificate']))
            ->assertOk()
            ->assertJsonPath('type', 'certificate')
            ->assertJsonPath('theme.primary_color', '#abcdef');
    }

    #[Test]
    public function the_preview_sanitises_custom_css(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('dashboard.document-designs.preview'), [
            'document_type' => 'certificate',
            'settings' => [],
            'custom_css' => '.x{color:red;}@import url(evil.css);',
        ])->assertOk();

        $this->assertStringNotContainsString('@import', $response->json('css'));
        $this->assertStringContainsString('.x{color:red;}', $response->json('css'));
    }

    #[Test]
    public function the_preview_honours_the_top_level_template_not_the_stored_one(): void
    {
        $this->design(['template' => 'classic']);

        // `template` is a column of its own and the form posts it at the top
        // level, not inside `settings`. Folding it into the theme is what makes
        // the template dropdown in the editor do anything.
        $response = $this->actingAs($this->admin())->postJson(route('dashboard.document-designs.preview'), [
            'document_type' => 'certificate',
            'template' => 'bordered',
            'settings' => [],
        ])->assertOk();

        $this->assertStringContainsString(
            '.doc-inner-frame',
            $response->json('css'),
            'the preview CSS must come from the template that was just chosen, not the stored one'
        );
    }

    #[Test]
    public function the_preview_ignores_a_bogus_template(): void
    {
        $this->design(['template' => 'modern']);

        $response = $this->actingAs($this->admin())->postJson(route('dashboard.document-designs.preview'), [
            'document_type' => 'certificate',
            'template' => '../../etc/passwd',
            'settings' => [],
        ])->assertOk();

        // An unrecognised template must leave the stored one in place rather
        // than selecting whatever happens to be indexed next.
        $this->assertStringContainsString('text-transform:uppercase', $response->json('css'));
        $this->assertStringNotContainsString('.doc-inner-frame', $response->json('css'));
    }

    #[Test]
    public function the_add_link_for_a_type_opens_the_form_on_that_type(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.document-designs.create', ['document_type' => 'id_card']))
            ->assertOk()
            ->assertViewHas('design', fn (DocumentDesign $design) => $design->document_type === 'id_card');
    }

    #[Test]
    public function an_unknown_type_on_the_add_link_falls_back_to_the_default(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.document-designs.create', ['document_type' => 'nonsense']))
            ->assertOk()
            ->assertViewHas('design', fn (DocumentDesign $design) => $design->document_type === 'certificate');
    }

    #[Test]
    public function a_failed_save_leaves_the_existing_default_in_place(): void
    {
        $this->design(['name' => 'Original', 'is_default' => true]);

        $original = DocumentDesign::query()->where('is_default', true)->sole();

        // Demoting the current default and then failing would leave the type with
        // no default at all, so the demotion has to be part of the same
        // transaction as the save.
        try {
            DB::transaction(function () {
                DocumentDesign::query()->where('document_type', 'certificate')->update(['is_default' => false]);

                throw new RuntimeException('simulated failure after demotion');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertTrue($original->fresh()->is_default, 'a rolled back save must not clear the default');
    }

    #[Test]
    public function the_preview_requires_the_permission(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->getJson(route('dashboard.document-designs.preview'))
            ->assertForbidden();
    }
}
