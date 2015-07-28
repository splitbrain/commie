<?php

namespace splitbrain\paste;

class PasteManager
{
    protected $savedir;

    function __construct() {
        $this->savedir = __DIR__ . '/../data/';
    }

    /**
     * Saves the given content under a new UID
     *
     * @param $content
     * @return bool|string UID or false on error
     */
    function savePaste($content)
    {
        $uid = $this->mkuid();
        do {
            $path = $this->fn($uid, 'paste');
        } while (file_exists($path));

        @mkdir(dirname($path), 0777, true);
        if(file_put_contents($path, $content)){
            return $uid;
        }
        return false;
    }

    /**
     * Loads a paste file
     *
     * @param $uid
     * @return bool|string the paste's content, false on error
     */
    function loadPaste($uid) {
        $path = $this->fn($uid, 'paste');
        if(!file_exists($path)) return false;
        return file_get_contents($path);
    }

    /**
     * Stores a comment
     *
     * @param string $uid
     * @param int $line
     * @param string $comment
     * @param string $user
     * @return bool|array
     */
    function saveComment($uid, $line, $comment, $user) {
        $paste = $this->fn($uid, 'paste');
        if(!file_exists($paste)) return false;

        $path = $this->fn($uid, 'comments');

        $data = compact('line', 'comment', 'user');
        $data['time'] = time();
        $data['color'] = substr(md5($user),0,6);
        $json = json_encode($data);
        if(file_put_contents($path, $json."\n", FILE_APPEND)) {
            return $data;
        }
        return false;
    }

    /**
     * Return all comments
     *
     * @param string $uid
     * @return array|bool
     */
    function loadComments($uid) {
        $paste = $this->fn($uid, 'paste');
        if(!file_exists($paste)) return false;

        $data = array();

        $path = $this->fn($uid, 'comments');
        if(!file_exists($path)) return $data;
        $json = file($path);
        foreach($json as $line) {
            $data[] = json_decode($line, true);
        }
        return $data;
    }

    /**
     * Get the full path to the file for the given UID and extension
     *
     * @param string $uid
     * @param string $ext
     * @return string
     */
    public function fn($uid, $ext)
    {
        $prefix = substr($uid, 0, 1);
        return $this->savedir . $prefix . '/' . $uid . '.' . $ext;
    }

    /**
     * Creates a unique name
     *
     * @link http://stackoverflow.com/a/3537633/172068
     * @param int $len
     * @return string
     */
    public function mkuid($len = 8)
    {

        $hex = md5("yourSaltHere" . uniqid("", true));

        $pack = pack('H*', $hex);
        $tmp = base64_encode($pack);

        $uid = preg_replace("#(*UTF8)[^A-Za-z0-9]#", "", $tmp);

        $len = max(4, min(128, $len));

        while (strlen($uid) < $len) {
            $uid .= gen_uuid(22);
        }

        return substr($uid, 0, $len);
    }

}