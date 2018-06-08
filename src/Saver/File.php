<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 08.06.18
 * Time: 19:08
 */

namespace satmaelstorm\LargeArrayWriter\Saver;


class File implements ISaver
{
    private $currentFile = null;
    private $gzip = false;
    private $mode = 'w';

    public function __construct(bool $gzip = false, string $mode = 'w')
    {
        $this->gzip = $gzip;
        $this->mode = $mode;
    }

    public function open(string $uri)
    {
        if ($this->gzip) {
            return $this->currentFile = gzopen($uri, $this->mode);
        } else {
            return $this->currentFile = fopen($uri, $this->mode);
        }
    }

    public function puts(string $str)
    {
        if ($this->gzip) {
            gzwrite($this->currentFile, $str);
        } else {
            fwrite($this->currentFile, $str);
        }
    }

    public function close()
    {
        if ($this->gzip) {
            gzclose($this->currentFile);
        } else {
            fclose($this->currentFile);
        }
    }
}