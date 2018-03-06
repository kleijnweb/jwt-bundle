<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\Jwt;

use KleijnWeb\JwtBundle\Jwt\JwtToken;
use KleijnWeb\JwtBundle\Jwt\SignatureValidator\SignatureValidator;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtTokenTest extends TestCase
{
    // @codingStandardsIgnoreStart
    const EXAMPLE_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ';
    const KID_TOKEN     = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleU9uZSJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.Zhhr_UtsTzjrBZmi8AAgJYqCINiiEc45v94_3nvxW1A';
    // @codingStandardsIgnoreEnd

    /**
     * @test
     */
    public function willDecodeClaimsOnConstruction()
    {
        $token = new JwtToken(self::EXAMPLE_TOKEN);
        $this->assertSame([
            'sub'   => '1234567890',
            'name'  => 'John Doe',
            'admin' => true,
        ], $token->getClaims());
    }

    /**
     * @test
     */
    public function willDecodeHeadersOnConstruction()
    {
        $token = new JwtToken(self::EXAMPLE_TOKEN);
        $this->assertSame([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ], $token->getHeader());
    }

    /**
     * @test
     * @return JwtToken
     */
    public function willDecodeWithArray()
    {
        $token = new JwtToken([
            'header' => [
                'alg' => 'HS256',
                'typ' => 'JWT',
            ],
            'claims' => [
                'sub'   => '1234567890',
                'name'  => 'John Doe',
                'admin' => true,
            ],
            'secret' => 'secret'
        ]);

        $this->assertSame(self::EXAMPLE_TOKEN, $token->getTokenString());

        return $token;
    }


    /**
     * @test
     */
    public function willResultNullWhenKidOmitted()
    {
        $token = new JwtToken(self::EXAMPLE_TOKEN);
        $this->assertNull($token->getKeyId());
    }

    /**
     * @test
     */
    public function canGetKidWhenPresent()
    {
        $token = new JwtToken(self::KID_TOKEN);
        $this->assertSame('keyOne', $token->getKeyId());
    }

    /**
     * @test
     */
    public function canGetSubject()
    {
        $this->assertSame('1234567890', $this->willDecodeWithArray()->getSubject());
    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function willFailWhenSignatureValidationIsUnsuccessful()
    {
        $validator = $this
            ->getMockBuilder(SignatureValidator::class)
            ->getMockForAbstractClass();

        $token = new JwtToken(self::EXAMPLE_TOKEN);
        $validator->expects($this->once())->method('isValid')->willReturn(false);
        $token->validateSignature('foobar', $validator);
    }


    /**
     * @test
     */
    public function willNitFailWhenSignatureValidationIsSuccessful()
    {
        $validator = $this
            ->getMockBuilder(SignatureValidator::class)
            ->getMockForAbstractClass();

        $token = new JwtToken(self::EXAMPLE_TOKEN);

        $validator->expects($this->once())->method('isValid')->willReturn(true);
        $token->validateSignature('foobar', $validator);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function willFailWhenTokenDoesNotContain3Segments()
    {
        $segments = explode('.', self::EXAMPLE_TOKEN);

        new JwtToken("{$segments[0]}.{$segments[1]}");
    }
}
