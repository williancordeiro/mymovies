<?php

namespace Tests\Integration\Controllers;

use Database\Populate\UsersPopulate;
use Lib\Authentication\Auth;

class AdminControllerTest extends ControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        UsersPopulate::populate();
    }

    public function test_should_render_admin_page_for_admin_user(): void
    {
        $response = $this->get(
            action: 'index',
            controllerName: 'App\Controllers\AdminController'
        );

        $this->assertStringContainsString('Admin Page', $response);
    }
}
