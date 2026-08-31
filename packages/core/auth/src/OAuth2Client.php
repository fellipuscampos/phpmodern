<?php

declare(strict_types=1);

namespace PhpModern\Auth;

use Closure;
use RuntimeException;

/**
 * A generic RFC 6749 authorization-code client (with RFC 7636 PKCE) —
 * "Login with GitHub/Google/whatever" without depending on any provider's
 * own SDK, the same "hand-build the protocol instead of a heavy vendor
 * dependency" choice this framework already made for SMTP and SSE. Works
 * against any spec-compliant provider: pass its authorize/token URLs and
 * this class never needs to know which one it's talking to.
 *
 * The actual HTTP POST is swappable via the constructor's $transport — the
 * default is a real stream_context-based POST (see post()), but tests
 * inject a fake to stay fast and network-free, the same "swap the real
 * implementation for a fake at the boundary" shape QueryHelper's Connection
 * or Mailer's implementations already use.
 *
 * @phpstan-type Transport Closure(string, array<string, string>): array{status: int, body: string}
 */
final class OAuth2Client
{
    /** @var Closure(string, array<string, string>): array{status: int, body: string} */
    private readonly Closure $transport;

    /**
     * @param Closure(string, array<string, string>): array{status: int, body: string}|null $transport
     */
    public function __construct(
        private readonly string $authorizeUrl,
        private readonly string $tokenUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        ?Closure $transport = null,
    ) {
        $this->transport = $transport ?? self::defaultTransport();
    }

    /** A CSRF-protection value the caller stores (e.g. in the session) and compares against the callback's `state` param. */
    public static function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** RFC 7636 §4.1: 43–128 URL-safe characters of randomness. */
    public static function generateCodeVerifier(): string
    {
        return self::base64UrlEncode(random_bytes(32));
    }

    /** RFC 7636 §4.2: S256 challenge derived from a verifier. */
    public static function codeChallengeFromVerifier(string $codeVerifier): string
    {
        return self::base64UrlEncode(hash('sha256', $codeVerifier, true));
    }

    /**
     * The URL to redirect the user to. Pass $codeChallenge (from
     * codeChallengeFromVerifier()) whenever the provider supports PKCE —
     * recommended even for a confidential client, required for a public one.
     *
     * @param list<string> $scopes
     */
    public function authorizationUrl(string $state, array $scopes = [], ?string $codeChallenge = null): string
    {
        $query = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ];

        if ($scopes !== []) {
            $query['scope'] = implode(' ', $scopes);
        }

        if ($codeChallenge !== null) {
            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = 'S256';
        }

        $separator = str_contains($this->authorizeUrl, '?') ? '&' : '?';

        return $this->authorizeUrl . $separator . http_build_query($query);
    }

    /**
     * Exchanges the callback's `code` for an access token. Pass the same
     * plaintext $codeVerifier used to derive the challenge in
     * authorizationUrl(), if PKCE was used.
     */
    public function exchangeCode(string $code, ?string $codeVerifier = null): OAuth2Token
    {
        $fields = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if ($codeVerifier !== null) {
            $fields['code_verifier'] = $codeVerifier;
        }

        return $this->tokenFromResponse(($this->transport)($this->tokenUrl, $fields));
    }

    /** Exchanges a refresh token for a new access token (RFC 6749 §6). */
    public function refresh(string $refreshToken): OAuth2Token
    {
        $fields = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        return $this->tokenFromResponse(($this->transport)($this->tokenUrl, $fields));
    }

    /** @param array{status: int, body: string} $response */
    private function tokenFromResponse(array $response): OAuth2Token
    {
        $decoded = json_decode($response['body'], true);

        if (!is_array($decoded)) {
            throw new RuntimeException("OAuth2 token endpoint returned a non-JSON body: {$response['body']}");
        }

        if (isset($decoded['error'])) {
            $description = $decoded['error_description'] ?? null;

            throw new OAuth2Exception((string) $decoded['error'], is_string($description) ? $description : null);
        }

        if ($response['status'] >= 400 || !isset($decoded['access_token'])) {
            throw new RuntimeException("OAuth2 token endpoint returned HTTP {$response['status']} with no access_token: {$response['body']}");
        }

        return new OAuth2Token(
            accessToken: (string) $decoded['access_token'],
            tokenType: isset($decoded['token_type']) ? (string) $decoded['token_type'] : 'bearer',
            expiresIn: isset($decoded['expires_in']) ? (int) $decoded['expires_in'] : null,
            refreshToken: isset($decoded['refresh_token']) ? (string) $decoded['refresh_token'] : null,
        );
    }

    /** @return Closure(string, array<string, string>): array{status: int, body: string} */
    private static function defaultTransport(): Closure
    {
        return static function (string $url, array $fields): array {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                    'content' => http_build_query($fields),
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
            ]);

            $body = @file_get_contents($url, false, $context);

            if ($body === false) {
                throw new RuntimeException("OAuth2Client: request to {$url} failed.");
            }

            $status = 0;
            foreach ($http_response_header as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $matches) === 1) {
                    $status = (int) $matches[1];
                }
            }

            return ['status' => $status, 'body' => $body];
        };
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
