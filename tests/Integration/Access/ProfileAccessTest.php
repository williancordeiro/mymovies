<?php

namespace Tests\Integration\Access;

class ProfileAccessTest extends BaseAccessTestCase
{
    public function test_profile_routes_should_require_authentication(): void
    {
        $responseAvatar = $this->client->request('POST', '/change/avatar', [
            'http_errors' => false
        ]);
        $this->assertEquals(401, $responseAvatar->getStatusCode());

        $responseBanner = $this->client->request('POST', '/change/banner', [
            'http_errors' => false
        ]);
        $this->assertEquals(401, $responseBanner->getStatusCode());
    }
}
