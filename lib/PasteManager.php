<?php

declare(strict_types=1);

namespace splitbrain\paste;

final class PasteManager
{
    public function __construct(
        private readonly string $savedir = __DIR__ . '/../data/',
    ) {}

    /**
     * Save the given content under a freshly generated UID.
     *
     * @return false|string the UID, or false on write failure
     */
    public function savePaste(string $content): false|string
    {
        do {
            $uid = $this->mkuid();
            $path = $this->fn($uid, 'paste');
        } while (file_exists($path));

        @mkdir(dirname($path), 0777, true);
        return file_put_contents($path, $content) !== false ? $uid : false;
    }

    /**
     * @return false|string the paste's content, or false if it does not exist
     */
    public function loadPaste(string $uid): false|string
    {
        $path = $this->fn($uid, 'paste');
        return file_exists($path) ? file_get_contents($path) : false;
    }

    /**
     * Append a comment to the paste's comments file.
     *
     * @return false|array the stored comment record, or false on failure
     */
    public function saveComment(string $uid, int $line, string $comment, string $user): false|array
    {
        if (!file_exists($this->fn($uid, 'paste'))) {
            return false;
        }

        $data = [
            'line'    => $line,
            'comment' => $comment,
            'user'    => $user,
            'time'    => time(),
            'color'   => substr(md5($user), 0, 6),
        ];

        $written = file_put_contents(
            $this->fn($uid, 'comments'),
            json_encode($data) . "\n",
            FILE_APPEND,
        );
        return $written !== false ? $data : false;
    }

    /**
     * @return false|array<int,array> list of comment records, or false if the paste does not exist
     */
    public function loadComments(string $uid): false|array
    {
        if (!file_exists($this->fn($uid, 'paste'))) {
            return false;
        }

        $path = $this->fn($uid, 'comments');
        if (!file_exists($path)) {
            return [];
        }

        $out = [];
        foreach (file($path) ?: [] as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $out[] = $decoded;
            }
        }
        return $out;
    }

    /**
     * Build the full path to the UID's file for the given extension.
     */
    public function fn(string $uid, string $ext): string
    {
        return $this->savedir . substr($uid, 0, 1) . '/' . $uid . '.' . $ext;
    }

    /**
     * Generate a random alphanumeric UID of the requested length.
     */
    private function mkuid(int $len = 8): string
    {
        $len = max(4, min(128, $len));
        $uid = '';
        while (strlen($uid) < $len) {
            $uid .= preg_replace('#[^A-Za-z0-9]#', '', base64_encode(random_bytes(16)));
        }
        return substr($uid, 0, $len);
    }
}
