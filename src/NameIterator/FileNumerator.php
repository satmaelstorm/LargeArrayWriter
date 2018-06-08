<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 08.06.18
 * Time: 19:00
 */

namespace satmaelstorm\LargeArrayWriter\NameIterator;


class FileNumerator implements INameIterator
{
    /** @var string */
    private $directoryDelimiter = "/";
    /** @var string */
    private $path = "/tmp";
    /** @var string */
    private $fileTemplate = "file_%NUM%.txt";
    /** @var int */
    private $pointer = 0;

    public function __construct(
        string $fileTemplate = "file_%NUM%.txt",
        string $path = "/tmp",
        string $directoryDelimiter = "/",
        int $startFrom = 0
    ) {
        $this->fileTemplate = $fileTemplate;
        $this->path = $path;
        $this->directoryDelimiter = $directoryDelimiter;
        $this->pointer = $startFrom;
    }

    public function next(): string
    {
        return $this->path.$this->directoryDelimiter.str_replace("%NUM%", $this->pointer++, $this->fileTemplate);
    }

}