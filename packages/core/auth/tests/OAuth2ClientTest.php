<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\OAuth2Client;
use PhpModern\Auth\OAuth2Exception;
use PHPUnit\Framework\TestCase;

final class OAuth2ClientTest extends TestCase
{
    public function test_authorization_url_carries_client_id_redirect_uri_state_and_scopes(): void
    {
        $client = new OAuth2Client(
            authorizeUrl: 'https://provider.test/authorize',
            tokenUrl: 'https://provider.test/token',
            clientId: 'abc123',
            clientSecret: 'shh',
            redirectUri: 'https://app.test/callback',
        );

        $url = $client->authorizationUrl('the-state-value', ['read:user', 'repo']);

        self::assertStringStartsWith('https://provider.test/authorize?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('code', $query['response_type']);
        self::assertSame('abc123', $query['client_id']);
        self::assertSame('https://app.test/callback', $query['redirect_uri']);
        self::assertSame('the-state-value', $query['state']);
        self::assertSame('read:user repo', $query['scope']);
        self::assertArrayNotHasKey('code_challenge', $query);
    }

    public function test_authorization_url_includes_pkce_params_when_a_challenge_is_given(): void
    {
        $client = new OAuth2Client('https://provider.test/authorize', 'https://provider.test/token', 'id', 'secret', 'https://app.test/callback');

        $url = $client->authorizationUrl('state', [], 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', $query['code_challenge']);
        self::assertSame('S256', $query['code_challenge_method']);
    }

    /**
     * RFC 7636 Appendix B's official test vector: proves
     * codeChallengeFromVerifier() is standards-correct, not just
     * internally self-consistent.
     */
    public function test_code_challenge_from_verifier_matches_the_rfc_7636_test_vector(): void
    {
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

        self::assertSame(
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            OAuth2Client::codeChallengeFromVerifier($verifier),
        );
    }

    public function test_generated_code_verifier_is_url_safe_and_long_enough_per_rfc_7636(): void
    {
        $verifier = OAuth2Client::generateCodeVerifier();

        self::assertGreaterThanOrEqual(43, strlen($verifier));
        self::assertLessThanOrEqual(128, strlen($verifier));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $verifier);
    }

    public function test_generate_state_returns_a_fresh_unpredictable_value_each_call(): void
    {
        self::assertNotSame(OAuth2Client::generateState(), OAuth2Client::generateState());
    }

    public function test_exchange_code_parses_a_successful_token_response(): void
    {
        $requests = [];
        $client = new OAuth2Client(
            'https://provider.test/authorize',
            'https://provider.test/token',
            'client-id',
            'client-secret',
            'https://app.test/callback',
            transport: function (string $url, array $fields) use (&$requests): array {
                $requests[] = ['url' => $url, 'fields' => $fields];

                return ['status' => 200, 'body' => json_encode([
                    'access_token' => 'the-access-token',
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'refresh_token' => 'the-refresh-token',
                ], JSON_THROW_ON_ERROR)];
            },
        );

        $token = $client->exchangeCode('the-auth-code', codeVerifier: 'the-verifier');

        self::assertSame('the-access-token', $token->accessToken);
        self::assertSame('bearer', $token->tokenType);
        self::assertSame(3600, $token->expiresIn);
        self::assertSame('the-refresh-token', $token->refreshToken);

        self::assertCount(1, $requests);
        self::assertSame('https://provider.test/token', $requests[0]['url']);
        self::assertSame('authorization_code', $requests[0]['fields']['grant_type']);
        self::assertSame('the-auth-code', $requests[0]['fields']['code']);
        self::assertSame('the-verifier', $requests[0]['fields']['code_verifier']);
        self::assertSame('client-secret', $requests[0]['fields']['client_secret']);
    }

    public function test_exchange_code_throws_an_o_auth_2_exception_on_a_provider_error(): void
    {
        $client = new OAuth2Client(
            'https://provider.test/authorize',
            'https://provider.test/token',
            'client-id',
            'client-secret',
            'https://app.test/callback',
            transport: fn (): array => ['status' => 400, 'body' => json_encode([
                'error' => 'invalid_grant',
                'error_description' => 'The authorization code has expired.',
            ], JSON_THROW_ON_ERROR)],
        );

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('invalid_grant: The authorization code has expired.');

        $client->exchangeCode('an-expired-code');
    }

    public function test_refresh_sends_a_refresh_token_grant(): void
    {
        $requests = [];
        $client = new OAuth2Client(
            'https://provider.test/authorize',
            'https://provider.test/token',
            'client-id',
            'client-secret',
            'https://app.test/callback',
            transport: function (string $url, array $fields) use (&$requests): array {
                $requests[] = $fields;

                return ['status' => 200, 'body' => json_encode(['access_token' => 'new-token', 'token_type' => 'bearer'], JSON_THROW_ON_ERROR)];
            },
        );

        $token = $client->refresh('the-old-refresh-token');

        self::assertSame('new-token', $token->accessToken);
        self::assertSame('refresh_token', $requests[0]['grant_type']);
        self::assertSame('the-old-refresh-token', $requests[0]['refresh_token']);
    }
}
