<?php

declare(strict_types=1);

namespace splitbrain\paste;

require_once __DIR__ . '/PasteManager.php';
require_once __DIR__ . '/Token.php';

const MAX_PASTE_BYTES   = 524288; // 512 KB
const MAX_COMMENT_BYTES = 4096;   // 4 KB
const MAX_USER_BYTES    = 64;
const COMMENT_DELAY     = 3;      // seconds, per-paste cooldown for savecomment

/**
 * Send an error status with a json `false` body and stop.
 */
function fail(int $code): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(false);
    exit;
}

/**
 * Strip everything that isn't an ASCII alphanumeric from a uid candidate.
 */
function clean_uid(mixed $raw): string
{
    return preg_replace('#(*UTF8)[^A-Za-z0-9]#', '', (string) ($raw ?? ''));
}

$paste  = new PasteManager();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$do     = match ($method) {
    'POST'  => (string) ($_POST['do'] ?? ''),
    default => (string) ($_GET['do']  ?? ''),
};

$ret = match ($do) {
    'save'         => handle_save($paste),
    'load'         => handle_load($paste),
    'loadcomments' => handle_loadcomments($paste),
    'savecomment'  => handle_savecomment($paste),
    default        => false,
};

header('Content-Type: application/json');
echo json_encode($ret);

function handle_save(PasteManager $paste): false|string
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        fail(405);
    }
    $content = (string) ($_POST['content'] ?? '');
    if (strlen($content) > MAX_PASTE_BYTES) {
        fail(413);
    }
    if (!Token::verify('save', (string) ($_POST['token'] ?? ''))) {
        fail(403);
    }
    return $paste->savePaste($content);
}

function handle_load(PasteManager $paste): false|string
{
    $uid = clean_uid($_GET['uid'] ?? null);
    if ($uid === '') {
        return false;
    }
    $content = $paste->loadPaste($uid);
    return $content === false ? false : htmlspecialchars($content);
}

function handle_loadcomments(PasteManager $paste): false|array
{
    $uid = clean_uid($_GET['uid'] ?? null);
    if ($uid === '') {
        return false;
    }
    $comments = $paste->loadComments($uid);
    if ($comments === false) {
        return false;
    }
    return [
        'comments' => $comments,
        'token'    => Token::issue($uid),
    ];
}

function handle_savecomment(PasteManager $paste): false|array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        fail(405);
    }
    $uid     = clean_uid($_POST['uid'] ?? null);
    $line    = (int) ($_POST['line'] ?? 0);
    $comment = (string) ($_POST['comment'] ?? '');
    $user    = (string) ($_POST['user'] ?? '');

    if ($uid === '') {
        fail(400);
    }
    if (strlen($comment) > MAX_COMMENT_BYTES) {
        fail(413);
    }
    if (strlen($user) > MAX_USER_BYTES) {
        fail(413);
    }
    if (!Token::verify($uid, (string) ($_POST['token'] ?? ''))) {
        fail(403);
    }

    $commentsPath = $paste->fn($uid, 'comments');
    if (file_exists($commentsPath) && (time() - filemtime($commentsPath)) < COMMENT_DELAY) {
        fail(429);
    }

    return $paste->saveComment($uid, $line, $comment, $user);
}
