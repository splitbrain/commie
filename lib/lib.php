<?php
namespace splitbrain\paste;

const MAX_PASTE_BYTES   = 524288;   // 512 KB
const MAX_COMMENT_BYTES = 4096;     // 4 KB
const MAX_USER_BYTES    = 64;
const TOKEN_TTL         = 1800;     // seconds
const COMMENT_DELAY     = 3;        // seconds, per paste

function commie_uid($raw) {
    return preg_replace("#(*UTF8)[^A-Za-z0-9]#", '', (string)$raw);
}

/**
 * Load (or first-time create) the HMAC secret used to sign action tokens.
 * Lives in data/.secret so it's covered by the existing data/ gitignore.
 */
function commie_secret() {
    static $cached = null;
    if ($cached !== null) return $cached;
    $path = __DIR__ . '/../data/.secret';
    if (!file_exists($path)) {
        $secret = bin2hex(random_bytes(32));
        @file_put_contents($path, $secret, LOCK_EX);
        @chmod($path, 0600);
        $cached = $secret;
        return $secret;
    }
    $cached = trim(file_get_contents($path));
    return $cached;
}

/**
 * Issue a short-lived token bound to $scope.
 */
function commie_token($scope) {
    $ts = time();
    return $ts . '.' . hash_hmac('sha256', $ts . ':' . $scope, commie_secret());
}

/**
 * Verify a token against $scope. HMAC is recomputed using the scope from the
 * request, so a token issued for one scope cannot be replayed against another.
 */
function commie_token_valid($scope, $token) {
    if (!is_string($token) || strpos($token, '.') === false) return false;
    list($ts, $hmac) = explode('.', $token, 2);
    if (!ctype_digit($ts)) return false;
    $expected = hash_hmac('sha256', $ts . ':' . $scope, commie_secret());
    if (!hash_equals($expected, $hmac)) return false;
    if (abs(time() - (int)$ts) > TOKEN_TTL) return false;
    return true;
}
