<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_library_grid(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="grid"', false)
            ->assertSee('css/memo.css', false);
    }

    public function test_landing_respects_lang_query(): void
    {
        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false);
    }

    public function test_landing_arabic_locale(): void
    {
        $this->get('/?lang=ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_api_videos_returns_json_array(): void
    {
        $this->getJson('/api/videos')
            ->assertOk()
            ->assertJson([]);
    }

    public function test_api_categories_returns_json_array(): void
    {
        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJson([]);
    }

    public function test_verify_valid_code_shows_verified_state(): void
    {
        $video = Video::create([
            'title' => 'Official Demo',
            'title_ar' => 'عرض رسمي',
            'slug' => 'official-demo',
            'status' => 'published',
            'is_public' => true,
            'duration' => 125,
            'verify_code' => '0K37-EUX1',
            'position' => 1,
        ]);

        $this->get('/verify/'.$video->verify_code)
            ->assertOk()
            ->assertSee('0K37-EUX1', false)
            ->assertSee(__('memo.verify.verified_badge'), false);
    }

    public function test_verify_invalid_code_shows_unverified_state(): void
    {
        $this->get('/verify/NOPE-NOPE')
            ->assertOk()
            ->assertSee(__('memo.verify.unverified_badge'), false);
    }

    public function test_verify_json_invalid_returns_404(): void
    {
        $this->getJson('/verify/NOPE-NOPE')
            ->assertNotFound()
            ->assertJson(['verified' => false]);
    }

    public function test_admin_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_admin_authed_renders_sidebar(): void
    {
        $user = User::factory()->create(['email' => 'admin@memo.store']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('id="side"', false)
            ->assertSee(__('memo.admin.nav.videos'), false);
    }

    public function test_admin_overview_api_authed(): void
    {
        $user = User::factory()->create(['email' => 'admin@memo.store']);

        $this->actingAs($user)
            ->getJson('/admin/api/overview')
            ->assertOk()
            ->assertJsonStructure(['stats', 'videos']);
    }

    public function test_login_rejects_bad_password(): void
    {
        User::factory()->create([
            'email' => 'admin@memo.store',
            'password' => 'correct-horse',
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'admin@memo.store',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_categories_page_renders(): void
    {
        $user = User::factory()->create(['email' => 'admin@memo.store']);

        $this->actingAs($user)
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('id="cList"', false);
    }

    public function test_csrf_meta_present_on_landing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('name="csrf-token"', false)
            ->assertDontSee('XSRF-TOKEN', false);
    }
}
