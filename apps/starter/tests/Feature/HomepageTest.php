<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HomepageTest extends TestCase
{
    public function test_homepage_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Find your Free Fire profile.');
    }

    public function test_developer_documentation_is_available(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('Developer guide')
            ->assertSee('/api/free-fire/v1/players/{uid}');
    }
}
