<?php

namespace Tests\Integration\Access;

use Core\Database\Database;
use Core\Env\EnvLoader;
use Database\Populate\UsersPopulate;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

abstract class BaseAccessTestCase extends TestCase
{
    protected Client $client;

    public function setUp(): void
    {
        parent::setUp();
        EnvLoader::init();
        Database::create();
        Database::migrate();
        UsersPopulate::populate();

        $this->client = new Client([
            'allow_redirects' => false,
            'base_uri' => 'http://nginx',
            'http_errors' => false
        ]);
    }

    public function tearDown(): void
    {
        Database::drop();
        parent::tearDown();
    }
}
