<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Jwt;

use KleijnWeb\JwtBundle\Jwt\Exception\InvalidTimeException;
use KleijnWeb\JwtBundle\Jwt\Exception\KeyTokenMismatchException;
use KleijnWeb\JwtBundle\Jwt\Exception\MissingClaimsException;
use KleijnWeb\JwtBundle\Jwt\SignatureValidator\HmacValidator;
use KleijnWeb\JwtBundle\Jwt\SignatureValidator\RsaValidator;
use KleijnWeb\JwtBundle\Jwt\SignatureValidator\SignatureValidator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtKey
{
    const TYPE_HMAC = 'HS256';
    const TYPE_RSA  = 'RS256';

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $issuer;

    /**
     * @var string
     */
    private $type = self::TYPE_HMAC;

    /**
     * @var array
     */
    private $audience = [];

    /**
     * @var int
     */
    private $minIssueTime;

    /**
     * @var array
     */
    private $requiredClaims = [];

    /**
     * @var int
     */
    private $issuerTimeLeeway;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var SecretLoader
     */
    private $secretLoader;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options['secret']) && !isset($options['loader'])) {
            throw new \InvalidArgumentException("Need a secret or a loader to verify tokens");
        }
        if (isset($options['secret']) && isset($options['loader'])) {
            throw new \InvalidArgumentException("Cannot configure both secret and loader");
        }
        $defaults = [
            'kid'          => null,
            'issuer'       => null,
            'audience'     => [],
            'minIssueTime' => null,
            'leeway'       => 0,
            'type'         => $this->type,
            'require'      => $this->requiredClaims,
        ];

        $options                = array_merge($defaults, $options);
        $this->issuer           = $options['issuer'];
        $this->audience         = $options['audience'];
        $this->type             = $options['type'];
        $this->minIssueTime     = $options['minIssueTime'];
        $this->requiredClaims   = $options['require'];
        $this->issuerTimeLeeway = $options['leeway'];
        $this->id               = $options['kid'];
        $this->secret           = isset($options['secret']) ? $options['secret'] : null;
        $this->secretLoader     = isset($options['loader']) ? $options['loader'] : null;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param JwtToken $token
     *
     * @throws \InvalidArgumentException
     */
    public function validateToken(JwtToken $token)
    {
        $this->validateHeader($token->getHeader());
        $this->validateClaims($token->getClaims());

        if (!$this->secretLoader) {
            $token->validateSignature($this->secret, $this->getSignatureValidator());

            return;
        }
        $token->validateSignature($this->secretLoader->load($token), $this->getSignatureValidator());
    }

    /**
     * @param array $header
     *
     * @throws \InvalidArgumentException
     */
    public function validateHeader(array $header)
    {
        if (!isset($header['alg'])) {
            throw new \InvalidArgumentException("Missing 'alg' in header");
        }
        if (!isset($header['typ'])) {
            throw new \InvalidArgumentException("Missing 'typ' in header");
        }
        if ($this->type !== $header['alg']) {
            throw new \InvalidArgumentException("Algorithm mismatch");
        }
    }

    /**
     * @param array $claims
     *
     * @throws \InvalidArgumentException
     */
    public function validateClaims(array $claims)
    {
        if ($this->requiredClaims) {
            $missing = array_diff_key(array_flip($this->requiredClaims), $claims);
            if (count($missing)) {
                throw new MissingClaimsException("Missing claims: " . implode(', ', $missing));
            }
        }
        if ($this->issuer && !isset($claims['iss'])) {
            throw new MissingClaimsException("Claim 'iss' is required");
        }
        if ($this->minIssueTime && !isset($claims['iat'])) {
            throw new MissingClaimsException("Claim 'iat' is required");
        }
        if (!empty($this->audience) && !isset($claims['aud'])) {
            throw new MissingClaimsException("Claim 'aud' is required");
        }
        if ((!isset($claims['sub']) || empty($claims['sub'])) && (!isset($claims['prn']) || empty($claims['prn']))) {
            throw new MissingClaimsException("Missing principle subject claim");
        }
        if (isset($claims['exp']) && $claims['exp'] + $this->issuerTimeLeeway < time()) {
            throw new InvalidTimeException("Token is expired by 'exp'");
        }
        if (isset($claims['iat']) && $claims['iat'] < ($this->minIssueTime + $this->issuerTimeLeeway)) {
            throw new InvalidTimeException("Server deemed your token too old");
        }
        if (isset($claims['nbf']) && ($claims['nbf'] - $this->issuerTimeLeeway) > time()) {
            throw new InvalidTimeException("Token not valid yet");
        }
        if (isset($claims['iss']) && $claims['iss'] !== $this->issuer) {
            throw new KeyTokenMismatchException("Issuer mismatch");
        }

        if (count($this->audience)) {
            if (isset($claims['aud']) &&
                (
                    (is_array($this->audience) && !in_array($claims['aud'], $this->audience))
                    || (!is_array($this->audience) && $claims['aud'] !== $this->audience)
                )
            ) {
                throw new KeyTokenMismatchException("Audience mismatch");
            }
        }
    }


    /**
     * @return SignatureValidator
     */
    public function getSignatureValidator(): SignatureValidator
    {
        if ($this->type == self::TYPE_RSA) {
            return new RsaValidator();
        }

        return new HmacValidator();
    }

    /**
     * Prevent accidental persistence of secret
     */
    final public function __sleep()
    {
        return [];
    }
}
