<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 10.05.17
 * Time: 15:29
 */

namespace satmaelstorm\LargeArrayWriter;

use satmaelstorm\LargeArrayWriter\NameIterator\INameIterator;
use satmaelstorm\LargeArrayWriter\Saver\ISaver;


/**
 * Class LAWriter (LargeArrayWriter 2.0)
 * @package satmaelstorm\LargeArrayWriter
 */
class LAWriter implements \Countable
{
    private $fileHeader = "";
    private $fileFooter = "";
    private $maxStrings = 50000;
    private $maxLength = 50 * 1024 * 1024; //50 Mb
    private $chunkSize = 1000;
    private $gzip = true;

    private $currentNum = 0;
    private $writtenFiles = [];
    private $strings = [];

    private $currentFile = null;
    private $currentFileSizeMb = 0;
    private $currentFileSizeStrings = 0;

    private $headerSize = 0;
    private $footerSize = 0;

    private $countTotal = 0;
    /** @var INameIterator  */
    private $nameIterator;
    /** @var ISaver  */
    private $saver;


    /**
     * LAWriter constructor.
     *
     * @param \satmaelstorm\LargeArrayWriter\NameIterator\INameIterator $nameIterator
     * @param \satmaelstorm\LargeArrayWriter\Saver\ISaver               $saver
     * @param string                                                    $header
     * @param string                                                    $footer
     * @param int                                                       $maxStrings
     * @param int                                                       $maxLength
     * @param int                                                       $chunkSize
     */
    public function __construct(
        INameIterator $nameIterator,
        ISaver $saver,
        string $header = "",
        string $footer = "",
        int $maxStrings = -1,
        int $maxLength = -1,
        int $chunkSize = 1000
    )
    {
        $this->nameIterator = $nameIterator;
        $this->saver = $saver;
        $this->fileHeader = $header;
        $this->fileFooter = $footer;
        $this->maxStrings = $maxStrings;
        $this->maxLength = $maxLength;
        $this->chunkSize = $chunkSize;
        $this->headerSize = strlen($this->fileHeader);
        $this->footerSize = strlen($this->fileFooter);
    }


    /**
     * Add string to file
     * @param string $string
     * @throws LargeArrayWriterException
     */
    public function addString(string $string)
    {
        $this->strings[] = $string;
        ++$this->countTotal;
        if (count($this->strings) >= $this->chunkSize) {
            $this->flushWriter();
        }
    }


    /**
     * Finalize current file, return list of written files
     * @return array
     * @throws LargeArrayWriterException
     */
    public function finalize()
    {
        $this->flushWriter();
        if (!is_null($this->currentFile)) {
            $this->finalizeFile();
        }
        return $this->writtenFiles;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->countTotal;
    }


    /**
     * @throws LargeArrayWriterException
     */
    protected function flushWriter()
    {
        $len = count($this->strings);
        $idx = 0;
        while ($idx < $len) {
            if (is_null($this->currentFile)) {
                $this->createNewFile();
            }
            if ($this->currentFileSizeStrings > 0 && $this->currentFileSizeStrings >= $this->maxStrings) {
                $this->finalizeFile();
                continue;
            }
            $str = $this->strings[$idx];
            $nextLen = strlen($str) + $this->footerSize; //in bytes
            if ($this->currentFileSizeMb > 0 && ($this->headerSize + $nextLen) >= $this->maxLength) {
                throw new LargeArrayWriterException("Result string too long (length with footer $nextLen bytes) : $str", LargeArrayWriterException::ERROR_STRING_TO_LONG);
            }
            if ($this->currentFileSizeMb > 0 && ($this->currentFileSizeMb + $nextLen) >= $this->maxLength) {
                $this->finalizeFile();
                continue;
            }
            $this->saver->puts($str);
            $this->currentFileSizeMb += strlen($str); //in bytes
            ++$this->currentFileSizeStrings;
            ++$idx;
        }
        unset($this->strings);
        $this->strings = [];
    }


    /**
     * @throws LargeArrayWriterException
     */
    protected function createNewFile()
    {
        $fileName = $this->nameIterator->next();
        $this->writtenFiles[] = $fileName;
        $this->currentFile = $this->saver->open($fileName);
        if (empty($this->currentFile)) {
            throw new LargeArrayWriterException("Can't open file $fileName for write!", LargeArrayWriterException::ERROR_CANT_OPEN_FILE);
        }
        $this->saver->puts($this->fileHeader);
        $this->currentFileSizeMb = $this->headerSize; //in bytes
    }

    protected function finalizeFile()
    {
        $this->saver->puts($this->fileFooter);
        $this->saver->close();
        ++$this->currentNum;
        $this->currentFileSizeMb = $this->currentFileSizeStrings = 0;
        $this->currentFile = null;
    }


    /**
     * @return string
     */
    public function getFileHeader() : string
    {
        return $this->fileHeader;
    }

    /**
     * @param string $fileHeader
     * @return LAWriter
     */
    public function setFileHeader(string $fileHeader) : self
    {
        $this->fileHeader = $fileHeader;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileFooter() : string
    {
        return $this->fileFooter;
    }

    /**
     * @param string $fileFooter
     * @return LAWriter
     */
    public function setFileFooter($fileFooter) : self
    {
        $this->fileFooter = $fileFooter;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxStrings() : int
    {
        return $this->maxStrings;
    }

    /**
     * @param int $maxStrings
     * @return LAWriter
     */
    public function setMaxStrings(int $maxStrings) : self
    {
        $this->maxStrings = $maxStrings;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength() : int
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     * @return LAWriter
     */
    public function setMaxLength(int $maxLength) : self
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getChunkSize() : int
    {
        return $this->chunkSize;
    }

    /**
     * @param int $chunkSize
     * @return LAWriter
     */
    public function setChunkSize(int $chunkSize) : self
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }
}
