<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 08.06.18
 * Time: 18:41
 */

namespace satmaelstorm\LargeArrayWriter\Saver;


interface ISaver
{
    public function open(string $uri);
    public function puts(string $str);
    public function close();
}