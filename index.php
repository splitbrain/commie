<?php
namespace splitbrain\paste;

require_once 'PasteManager.php';

$paste = new PasteManager();

if(isset($_REQUEST['uid'])) {
    $_REQUEST['uid'] = preg_replace("#(*UTF8)[^A-Za-z0-9]#", '', $_REQUEST['uid']);
}

switch ($_REQUEST['do']) {
    case 'save':
        $ret = $paste->savePaste($_REQUEST['content']);
        break;
    case 'load':
        $ret = htmlspecialchars($paste->loadPaste($_REQUEST['uid']));
        break;
    case 'loadcomments':
        $ret = $paste->loadComments($_REQUEST['uid']);
        break;
    case 'savecomment':
        $ret = $paste->saveComment($_REQUEST['uid'], (int) $_REQUEST['line'], $_REQUEST['comment'], 'user');
        break;
    default:
        $ret = false;
}

header('Content-Type: application/json');
echo json_encode($ret);

