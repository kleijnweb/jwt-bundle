<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\Firewall;

use KleijnWeb\JwtBundle\Authentication\JwtAuthenticatedToken;
use KleijnWeb\JwtBundle\Authentication\JwtAuthenticationToken;
use KleijnWeb\JwtBundle\Firewall\JwtAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtAuthenticationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManagerMock;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorageMock;

    protected function setUp()
    {
        $this->tokenStorageMock          = $this->getMockForAbstractClass(TokenStorageInterface::class);
        $this->authenticationManagerMock = $this->getMockForAbstractClass(AuthenticationManagerInterface::class);
    }

    /**
     * @test
     */
    public function willSkipAuthenticationIfHeaderNotSetInRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->authenticationManagerMock;
        $mock->expects($this->never())->method('authenticate');

        (new JwtAuthenticationListener($this->tokenStorageMock, $this->authenticationManagerMock))
            ->handle($this->createKernelEventWithRequest(new Request()));
    }

    /**
     * @test
     */
    public function canCreateTokenFromBearerHeaderByDefault()
    {
        $tokenString = 'TheJwtToken';

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->authenticationManagerMock;
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($this->callback(function (JwtAuthenticationToken $token) use ($tokenString) {
                return $token->getCredentials() === $tokenString;
            }));

        (new JwtAuthenticationListener($this->tokenStorageMock, $this->authenticationManagerMock))
            ->handle($this->createKernelEventWithRequest(
                $this->createRequestWithServerVar('HTTP_AUTHORIZATION', "Bearer $tokenString"))
            );

    }

    /**
     * @test
     */
    public function canCreateTokenFromAuthHeaderWithoutBearerPrefixByDefault()
    {
        $tokenString = 'TheJwtToken';

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->authenticationManagerMock;
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($this->callback(function (JwtAuthenticationToken $token) use ($tokenString) {
                return $token->getCredentials() === $tokenString;
            }));

        (new JwtAuthenticationListener($this->tokenStorageMock, $this->authenticationManagerMock))
            ->handle($this->createKernelEventWithRequest(
                $this->createRequestWithServerVar('HTTP_AUTHORIZATION', $tokenString))
            );
    }

    /**
     * @test
     */
    public function canCreateTokenFromCustomHeader()
    {
        $tokenString = 'TheJwtToken';

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->authenticationManagerMock;
        $mock->expects($this->once())
            ->method('authenticate')
            ->with($this->callback(function (JwtAuthenticationToken $token) use ($tokenString) {
                return $token->getCredentials() === $tokenString;
            }));

        (new JwtAuthenticationListener($this->tokenStorageMock, $this->authenticationManagerMock, 'X-Token'))
            ->handle($this->createKernelEventWithRequest(
                $this->createRequestWithServerVar('HTTP_X_TOKEN', $tokenString))
            );
    }

    /**
     * @test
     */
    public function willSkipAuthenticationIfCustomHeaderNotSetInRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->authenticationManagerMock;
        $mock->expects($this->never())->method('authenticate');

        (new JwtAuthenticationListener($this->tokenStorageMock, $this->authenticationManagerMock, 'X-Token'))
            ->handle($this->createKernelEventWithRequest(
                $this->createRequestWithServerVar('HTTP_AUTHORIZATION', 'something'))
            );
    }

    /**
     * @test
     */
    public function willSetTokenReturnedByAuthenticationHeaderOnStorage()
    {

        $userStub = $this->getMockForAbstractClass(UserInterface::class);
        $userStub->expects($this->any())->method('getRoles')->willReturn([]);

        $authenticatedToken = new JwtAuthenticatedToken($userStub);

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->authenticationManagerMock;
        $mock->expects($this->once())
            ->method('authenticate')
            ->willReturnCallback(function () use($authenticatedToken) {
                return $authenticatedToken;
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->tokenStorageMock;
        $mock->expects($this->once())
            ->method('setToken')
            ->with($authenticatedToken);

        (new JwtAuthenticationListener($this->tokenStorageMock, $this->authenticationManagerMock))
            ->handle($this->createKernelEventWithRequest(
                $this->createRequestWithServerVar('HTTP_AUTHORIZATION', 'something'))
            );
    }

    private function createRequestWithServerVar(string $name, string $value): Request
    {
        return new Request([], [], [], [], [], [$name => $value]);
    }

    private function createKernelEventWithRequest(Request $request): GetResponseEvent
    {
        $mock = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        return $mock;
    }

}
