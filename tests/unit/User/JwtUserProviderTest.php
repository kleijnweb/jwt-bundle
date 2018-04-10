<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\User;

use KleijnWeb\JwtBundle\Jwt\JwtToken;
use KleijnWeb\JwtBundle\User\JwtUser;
use KleijnWeb\JwtBundle\User\JwtUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtUserProviderTest extends TestCase
{
    /**
     * @var JwtUserProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->provider = new JwtUserProvider();
    }

    /**
     * @test
     */
    public function canLoadUserPopulatedFromToken()
    {
        $username = 'johndoe';
        $claims   = ['sub' => $username, 'iat' => time()];
        $user     = $this->loadUser($claims);
        $this->assertSame($username, $user->getUsername());
        $this->assertSame($claims, $user->getClaims());
        $this->assertSame([JwtUserProvider::BASE_ROLE], $user->getRoles());
    }

    /**
     * @test
     */
    public function willAddGroupsFromAudienceClaims()
    {
        $claims = ['sub' => 'johndoe', 'aud' => 'admin'];
        $user   = $this->loadUser($claims);
        $this->assertSame([JwtUserProvider::BASE_ROLE, 'ROLE_ADMIN'], $user->getRoles());
    }

    /**
     * @test
     */
    public function onlySupportsJwtUser()
    {
        $this->assertFalse($this->provider->supportsClass(UserInterface::class));
        $this->assertTrue($this->provider->supportsClass(JwtUser::class));
    }

    /**
     * @test
     */
    public function refreshUserReturnsNewInstance()
    {
        $username = 'johndoe';
        $user     = $this->loadUser(['sub' => $username]);
        $this->assertNotSame($user, $this->provider->refreshUser($user));
    }

    /**
     * @test
     */
    public function canCallSuperfluousMethods()
    {
        $user = $this->loadUser(['sub' => 'johndoe']);
        $user->eraseCredentials();
        $user->getSalt();
        $user->getPassword();
    }

    /**
     * @param array $claims
     * @return JwtUser
     */
    private function loadUser(array $claims)
    {
        $token = $this->createToken($claims);
        $this->provider->setClaimsUsingToken($token);
        return $this->provider->loadUserByUsername($claims['sub']);
    }

    /**
     * @param array $claims
     *
     * @return JwtToken
     */
    private function createToken(array $claims)
    {
        return new JwtToken([
            'header' => [
                'alg' => 'HS256',
                'typ' => 'JWT',
                'kid' => 'keyOne'
            ],
            'claims' => $claims,
            'secret' => 'secret'
        ]);
    }
}
