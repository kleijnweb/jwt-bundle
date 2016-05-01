<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Tests\Functional;

use KleijnWeb\JwtBundle\Tests\Authenticator\AuthenticatorTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class FunctionalTest extends WebTestCase
{
    // @codingStandardsIgnoreStart
    const PSK_TOKEN = AuthenticatorTest::TEST_TOKEN;
    const HMAC_TOKEN = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleVR3byJ9.eyJwcm4iOiJqb2huIiwiaXNzIjoiaHR0cDovL2FwaS5zZXJ2ZXIyLmNvbS9vYXV0aDIvdG9rZW4ifQ.vdGhD5E4Ibj2Tndlh_0pPgJsOuRUpAn1QYu5miB6qwjrXhKCicuTKOuC9x2_2ErUOApv5KiblYds_gcWONdGKx1tQyQa1dsuhrkiVn_VJAsaaix8nJiHAuNv-ukm8mnSWJoVuOcTQIQG8IaupviyphEAEdjrm9QQhvzERgdFUT4bdCdfywrC37oYEAH5bHpiiUK2UzyNuUIHwOP_gWODodbEWRJOxtefwJ_vdpqHvSZzyW7Vei4mCtr2vE1k2qBvG_Qjw2ebLfEdX58k6-eYa7phle9hYjA_q-I8Y-S1ulBiVf_tpvayk8-4lWup9Wbg_BT2vDJOidQgM4l9jV9QHg';
    const DYN_HMAC_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleVRocmVlIn0.eyJwcm4iOiJqb2UiLCJpc3MiOiJodHRwOi8vYXBpLnNlcnZlcjIuY29tL29hdXRoMi90b2tlbiJ9.fv9yrTk3AnPTle_ikBY2EjIFhb1xaxKO4-Vop2AxnME';
    // @codingStandardsIgnoreEnd

    /**
     * @test
     */
    public function canGetUnsecuredContentWithoutToken()
    {
        $client = $this->createClient();
        $client->request('GET', '/unsecured');
        $this->assertSame('UNSECURED CONTENT', $client->getResponse()->getContent());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function cannotGetSecuredContentWithoutToken()
    {
        $client = $this->createClient();
        $client->request('GET', '/secured');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function cannotGetSecuredContentWitInvalidToken()
    {
        $client = $this->createClient();
        $server = ['HTTP_AUTHORIZATION' => 'Bearer foo'];
        $client->request('GET', '/secured', $parameters = [], $files = [], $server);
    }

    /**
     * @test
     */
    public function canGetSecuredContentWitValidPskToken()
    {
        $client = $this->createClient();
        $server = ['HTTP_AUTHORIZATION' => 'Bearer ' . self::PSK_TOKEN];
        $client->request('GET', '/secured', $parameters = [], $files = [], $server);
        $this->assertSame('SECURED CONTENT', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function canGetSecuredContentWitValidHmacToken()
    {
        $client = $this->createClient();
        $server = ['HTTP_AUTHORIZATION' => 'Bearer ' . self::HMAC_TOKEN];
        $client->request('GET', '/secured', $parameters = [], $files = [], $server);
        $this->assertSame('SECURED CONTENT', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function canGetSecuredContentWithSecretLoader()
    {
        $client = $this->createClient();
        $server = ['HTTP_AUTHORIZATION' => 'Bearer ' . self::DYN_HMAC_TOKEN];
        $client->request('GET', '/secured-with-secret-loader', $parameters = [], $files = [], $server);
        $this->assertSame('CONTENT SECURED WITH SECRET LOADER', $client->getResponse()->getContent());
    }
}
