<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Authenticator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class Decoder
{
    /**
     * @var array
     */
    private static $messages = [
        JSON_ERROR_NONE           => 'No error',
        JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
        JSON_ERROR_CTRL_CHAR      => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX         => 'Syntax error',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    ];

    /**
     * @param string $base64Encoded
     *
     * @return array
     */
    public function decode(string $base64Encoded): array
    {
        return $this->jsonDecode($this->base64Decode($base64Encoded));
    }

    /**
     * @param string $plain
     *
     * @return array
     */
    public function jsonDecode(string $plain): array
    {
        $data = json_decode($plain, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException(self::$messages[json_last_error()]);
        }

        return $data;
    }

    /**
     * @param string $base64Encoded
     *
     * @return string
     */
    public function base64Decode(string $base64Encoded): string
    {
        if ($remainder = strlen($base64Encoded) % 4) {
            $base64Encoded .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($base64Encoded, '-_', '+/'));
    }
}
