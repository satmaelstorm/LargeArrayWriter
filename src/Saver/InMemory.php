<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 08.10.18
 * Time: 10:53
 */

namespace satmaelstorm\LargeArrayWriter\Saver;

/**
 * Class InMemory
 * @package satmaelstorm\LargeArrayWriter\Saver
 */
class InMemory implements ISaver
{

    private $content = "";
    private $uri = null;
    private $contents = [];

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function getContent(?string $uri = null): ?string
    {
        if (null !== $uri){
            return $this->contents[$uri] ?? null;
        }
        return $this->content;
    }

    public function open(string $uri)
    {
        if (null !== $this->uri){
            $this->contents[$this->uri] = $this->content;
        }
        $this->uri = $uri;
        $this->content = "";
        return $this;
    }

    public function puts(string $str)
    {
        $this->content .= $str;
    }

    public function close()
    {
        return $this;
    }

}