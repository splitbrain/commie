<?php
namespace splitbrain\paste;

require_once __DIR__.'/lib.php';
require_once __DIR__.'/PasteManager.php';

$paste = new PasteManager();

$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : '';
$method = $_SERVER['REQUEST_METHOD'];
$ret = false;

switch ($do) {
    case 'save':
        if ($method !== 'POST') { http_response_code(405); break; }
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $token   = isset($_POST['token']) ? $_POST['token'] : '';
        if (strlen($content) > MAX_PASTE_BYTES) { http_response_code(413); break; }
        if (!commie_token_valid('save', $token)) { http_response_code(403); break; }
        $ret = $paste->savePaste($content);
        break;

    case 'load':
        $uid = commie_uid(isset($_GET['uid']) ? $_GET['uid'] : '');
        $ret = htmlspecialchars($paste->loadPaste($uid));
        break;

    case 'loadcomments':
        $uid = commie_uid(isset($_GET['uid']) ? $_GET['uid'] : '');
        $ret = [
            'comments' => $paste->loadComments($uid),
            'token'    => commie_token($uid),
        ];
        break;

    case 'savecomment':
        if ($method !== 'POST') { http_response_code(405); break; }
        $uid     = commie_uid(isset($_POST['uid']) ? $_POST['uid'] : '');
        $line    = isset($_POST['line']) ? (int) $_POST['line'] : 0;
        $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
        $user    = isset($_POST['user']) ? $_POST['user'] : '';
        $token   = isset($_POST['token']) ? $_POST['token'] : '';
        if (strlen($comment) > MAX_COMMENT_BYTES) { http_response_code(413); break; }
        if (strlen($user) > MAX_USER_BYTES) { http_response_code(413); break; }
        if (!commie_token_valid($uid, $token)) { http_response_code(403); break; }
        $commentsPath = $paste->fn($uid, 'comments');
        if (file_exists($commentsPath) && (time() - filemtime($commentsPath)) < COMMENT_DELAY) {
            http_response_code(429);
            break;
        }
        $ret = $paste->saveComment($uid, $line, $comment, $user);
        break;

    default:
        $ret = false;
}

header('Content-Type: application/json');
echo json_encode($ret);
