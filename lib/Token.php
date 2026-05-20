<?php

declare(strict_types=1);

namespace splitbrain\paste;

/**
 * Stateless HMAC action tokens.
 *
 * Tokens carry their own timestamp and a signature over `<timestamp>:<scope>`
 * keyed by a per-installation secret. Verification recomputes the HMAC against
 * the scope passed at verification time, so a token issued for one action
 * cannot be replayed against another.
 */
final class Token
{
    /** Maximum age of a token, in seconds. */
    public const TTL = 1800;

    private const SECRET_PATH = __DIR__ . '/../data/.secret';

    private static ?string $secret = null;

    private function __construct() {}

    public static function issue(string $scope): string
    {
        $ts = time();
        return $ts . '.' . hash_hmac('sha256', $ts . ':' . $scope, self::secret());
    }

    public static function verify(string $scope, string $token): bool
    {
        if (!str_contains($token, '.')) {
            return false;
        }
        [$ts, $hmac] = explode('.', $token, 2);
        if (!ctype_digit($ts)) {
            return false;
        }
        $expected = hash_hmac('sha256', $ts . ':' . $scope, self::secret());
        return hash_equals($expected, $hmac)
            && abs(time() - (int) $ts) <= self::TTL;
    }

    private static function secret(): string
    {
        if (self::$secret !== null) {
            return self::$secret;
        }

        if (!file_exists(self::SECRET_PATH)) {
            $fresh = bin2hex(random_bytes(32));
            @file_put_contents(self::SECRET_PATH, $fresh, LOCK_EX);
            @chmod(self::SECRET_PATH, 0600);
            return self::$secret = $fresh;
        }

        return self::$secret = trim((string) file_get_contents(self::SECRET_PATH));
    }
}
