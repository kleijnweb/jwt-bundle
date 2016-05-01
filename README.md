# KleijnWeb\JwtBundle 
[![Build Status](https://travis-ci.org/kleijnweb/jwt-bundle.svg?branch=master)](https://travis-ci.org/kleijnweb/jwt-bundle)
[![Coverage Status](https://coveralls.io/repos/github/kleijnweb/jwt-bundle/badge.svg?branch=master)](https://coveralls.io/github/kleijnweb/jwt-bundle?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kleijnweb/jwt-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kleijnweb/jwt-bundle/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/kleijnweb/jwt-bundle/v/stable)](https://packagist.org/packages/kleijnweb/jwt-bundle)

Integrate OAuth 2.0 compatible JWT API tokens for authentication.

Go to the [release page](https://github.com/kleijnweb/jwt-bundle/releases) to find details about the latest release.

For an example see [swagger-bundle-example](https://github.com/kleijnweb/swagger-bundle-example).

*NOTE:* JwtBundle is compatible with PHP 5.4 and does not work with Symfony 3.0 (yet).   

## Install And Configure

Install using composer (`composer require kleijnweb/jwt-bundle`). You want to check out the [release page](https://github.com/kleijnweb/jwt-bundle/releases) to ensure you are getting what you want and optionally verify your download.

## Authentication

JwtBundle comes with a `Authenticator` which implements a OAuth 2 compatible token-based authentication method. 
The role of the server with JwtBundle in OAuth terms is "Resource Server" (ie your app has some resources belonging to the "Resource Owner" that a client program wants access to). 

The token is validated using standard (reserved) JWT claims:

| Name  | Type | Description |
|-------|---------|-------|
| `exp` | int [1] | Expiration time must be omitted [3] or be smaller than `time() + leeway` [2]. |
| `nbf` | int [1] | "Not before", token validity start time, must be omitted [3] or greater than or equal to `time() - leeway` [2]. |
| `iat` | int [1] | The time the token was issued, must be omitted [3] or smaller than configured `minIssueTime + leeway`. Required when `minIssueTime` configured.  |
| `iss` | string | Issuer of the token, must match configured `issuer`. Required when `issuer` configured. |
| `aud` | string | JWT "audience", must be omitted [3] or match configured `audience` if configured. Required when `audience` configured. |
| `prn` | string | JWT "principal". Used as `username` for Symfony Security integration. Always required, without it the "Resource Owner cannot be identified. |
| `jti` | string | JWT "ID". Not used, will be ignored. |
| `typ` | string | Not used, will be ignored. |
 
 - [1] Unix time
 - [2] The `leeway` allows a difference in seconds between the issuer of the token and the server running your app with JwtBundle. Keep at a low number, defaults to 0.
 - [3] Mark any claim required, including custom (non-reserved) ones, using the `require` configuration option.
 
All other claims encountered are ignored. The JWT header is checked for `kid` (see below) and `alg`, which must match the `type` value of the key configuration.

### Keys

`Authenticator` supports multiple keys, and allows all options to be configured per `kid` (key ID, which must be included in the JWT header when more than 1 key is configured):

```yml
jwt: 
   keys:
      keyOne: # Only one key, 'kid' is optional (but must match when provided)
        issuer: http://api.server.com/oauth2/token # OAuth2 example, but could be any string value
        audience: ~ # NULL, accept any
        minIssueTime: 1442132949 # Reject 'old' tokens, regardless of 'exp'
        require: [nbf, exp, my-claim] # Mark claims as required
        leeway: 5 # Allow 5 seconds of time de-synchronization between this server and api.server.com
```

Clients should pass the token using an `Authentication: Bearer` header, eg:

```
Authentication: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ
```

While this is compatible with OAuth 2.0, use of such a protocol is outside of the scope of JwtBundle and entirely optional. For more information on using JWT Bearer tokens in OAuth, refer to [this spec](http://tools.ietf.org/html/draft-ietf-oauth-jwt-bearer-07).

JwtBundle and the issuer must share a secret in order for JwtBundle to be able to verify tokens. You can choose between a *pre shared key* (PSK) or *asymmetric keys*. 

```yml
jwt:
   keys:
      keyOne: # Must match 'kid'
        issuer: http://api.server1.com/oauth2/token
        secret: 'A Pre-Shared Key'
        # type:  Defaults to HS256 (HMACSHA256). All options: HS256, HS512, RS256 and RS512
      keyTwo: # Must match 'kid'
        issuer: http://api.server2.com/oauth2/token
        type: RS256 # RSA SHA256, needed for asymmetric keys
        secret: |
                -----BEGIN PUBLIC KEY-----
                MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwND1VMVJ3BC/aM38tQRH
                2GDHecXE8EsGoeAeBR5dFt3QC1/Eoub/F2kee3RBtI6I+kDBjrSDz5lsqh3Sm7N/
                47fTKZLvdBaHbCuYXVBQ2tZeEiUBESnsY2HUzXDlqSyDWohuiYeeL6gewxe1CnSE
                0l8gYZ0Tx4ViPFYulva6siew0f4tBuSEwSPiKZQnGcssQYJ/VevTD6L4wGoDhkXV
                VvJ+qiNgmXXssgCl5vHs22y/RIgeOnDhkj81aB9Evx9iR7DOtyRBxnovrbN5gDwX
                m6IDw3fRhZQrVwZ816/eN+1sqpIMZF4oo4kRA4b64U04ex67A/6BwDDQ3LH0mD4d
                EwIDAQAB
                -----END PUBLIC KEY-----
    
```

To use *asymmetric keys*, `type` MUST be set to `RS256` or `RS512`. The secret in this case is the public key of the issuer.

### Loading Secrets From An External Source

Instead of configuring secrets statically, they can also be loaded dynamically, using any data available in the JWT token. Example configuration:

```yml
jwt:
   keys:
    keyThree: # Must match 'kid'
      issuer: http://api.server1.com/oauth2/token
      loader: 'my.loader.di.key'
    
```

The loader must implement `KleijnWeb\JwtBundle\Authenticator\SecretLoader`. A simple example that loads the secret from an ambiguous data store:

```php
use KleijnWeb\JwtBundle\Authenticator\JwtToken;
use KleijnWeb\JwtBundle\Authenticator\SecretLoader;

class SimpleSecretLoader implements SecretLoader
{
    /**
     * @var DataStore
     */
    private $store;

    /**
     * @param DataStore $store
     */
    public function __construct(DataStore $store)
    {
        $this->store = $store;
    }

    /**
     * @param JwtToken $token
     *
     * @return string
     */
    public function load(JwtToken $token)
    {
        return $this->store->loadSecretByUsername($token->getClaims()['prn']);
    }
}
```

You could use any information available in the token, such as the `kid`, `alg` or any custom claims. You cannot configure both `secret` and `loader`. Be sure to throw an `AuthenticationException` when appropriate (eg missing claims needed for loading secret). 

### Integration Into Symfony Security

When enabled, `Authenticator` will be used for any operations referencing a `SecurityDefinition` of type `apiKey` or `oath2`. You will need a *user provider*, which will be passed the
 `prn` value when invoking `loadUserByUsername`. Trivial example using 'in memory':
 
```yml
security:
    firewalls:
        secured_area:
            pattern: ^/
            stateless: true
            simple_preauth:
                authenticator: jwt.authenticator
            provider: in_memory

    providers:
        in_memory:
            memory:
                users:
                    joe:
                        roles: 'IS_AUTHENTICATED_FULLY'
```
   
## License

KleijnWeb\JwtBundle is made available under the terms of the [LGPL, version 3.0](https://spdx.org/licenses/LGPL-3.0.html#licenseText).
