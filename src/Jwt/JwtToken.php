<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Jwt;

use KleijnWeb\JwtBundle\Jwt\SignatureValidator\SignatureValidator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtToken
{
    /**
     * @var array
     */
    private $claims = [];

    /**
     * @var array
     */
    private $header = [];

    /**
     * @var int
     */
    private $payload;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var string
     */
    private $tokenString;

    /**
     * @param mixed $tokenData
     */
    public function __construct($tokenData)
    {
        if (is_array($tokenData)) {
            $this->setTokenFromParams($tokenData['header'], $tokenData['claims'], $tokenData['secret']);
        } else {
            $this->setTokenFromString($tokenData);
        }
    }

    /**
     * @param string $tokenString
     */
    public function setTokenFromString(string $tokenString)
    {
        $this->tokenString = $tokenString;
        $segments          = explode('.', $tokenString);

        if (count($segments) !== 3) {
            throw new \InvalidArgumentException("Not a JWT token string");
        }

        list($headerBase64, $claimsBase64, $signatureBase64) = $segments;

        $this->payload = "{$headerBase64}.{$claimsBase64}";

        $decoder         = new Decoder();
        $this->header    = $decoder->decode($headerBase64);
        $this->claims    = $decoder->decode($claimsBase64);
        $this->signature = $decoder->base64Decode($signatureBase64);
    }

    /**
     * @param array  $header
     * @param array  $claims
     * @param string $secret
     */
    public function setTokenFromParams(array $header, array $claims, string $secret)
    {
        $this->header = $header;
        $this->claims = $claims;

        $encoder      = new Encoder();
        $headerBase64 = $encoder->encode($header);
        $claimsBase64 = $encoder->encode($claims);

        $this->payload = "{$headerBase64}.{$claimsBase64}";

        $this->signature = hash_hmac(
            'sha256',
            $this->payload,
            $secret,
            true
        );
        $signatureBase64 = $encoder->base64Encode($this->signature);

        $segments          = compact('headerBase64', 'claimsBase64', 'signatureBase64');
        $this->tokenString = implode('.', $segments);
    }

    /**
     * @return string|null
     */
    public function getKeyId()
    {
        return isset($this->header['kid']) ? $this->header['kid'] : null;
    }

    /**
     * @param string             $secret
     * @param SignatureValidator $validator
     *
     * @throws \InvalidArgumentException
     */
    public function validateSignature(string $secret, SignatureValidator $validator)
    {
        if (!$validator->isValid($this->payload, $secret, $this->signature)) {
            throw new \InvalidArgumentException("Invalid signature");
        }
    }

    /**
     * @return array|string
     */
    public function getSubject()
    {
        if (isset($this->claims['sub'])) {
            return $this->claims['sub'];
        }

        return $this->claims['prn'];
    }

    /**
     * @return array
     */
    public function getClaims(): array
    {
        return $this->claims;
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return string
     */
    public function getTokenString(): string
    {
        return $this->tokenString;
    }
}
