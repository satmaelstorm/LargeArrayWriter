<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 10.05.17
 * Time: 15:29
 */

namespace satmaelstorm\LargeArrayWriter;


class LargeArrayWriter implements \Countable
{
    private $path = "/tmp";
    private $nameTemplate = "file_%NUM%.txt";
    private $fileHeader = "";
    private $fileFooter = "";
    private $maxStrings = 50000;
    private $maxLength = 50 * 1024 * 1024; //50 Mb
    private $chunkSize = 1000;
    private $gzip = true;

    private $directoryDelimiter = "/";

    private $currentNum = 0;
    private $writtenFiles = [];
    private $strings = [];

    private $currentFile = null;
    private $currentFileSizeMb = 0;
    private $currentFileSizeStrings = 0;

    private $headerSize = 0;
    private $footerSize = 0;

    private $countTotal = 0;


    /**
     * LargeArrayWriter constructor.
     * @param string $nameTemplate
     * @param string $path
     * @param bool $gzip
     * @param string $header
     * @param string $footer
     * @param int $maxStrings
     * @param int $maxLength
     * @param int $chunkSize
     * @param string $directoryDelimiter
     * @throws \Exception
     */
    public function __construct(
        string $nameTemplate = "file_%NUM%.txt",
        string $path = "/tmp",
        bool $gzip = true,
        string $header = "",
        string $footer = "",
        int $maxStrings = 50000, //50K strings - for sitemaps
        int $maxLength = 50 * 1024 * 1024, //50 Mb - for sitemaps
        int $chunkSize = 1000,
        string $directoryDelimiter = "/"
    )
    {
        $this->path = $path;
        $b = $this->ensureDirectory($this->path);
        if (!$b){
            throw new \Exception("Can't create directory {$this->path}");
        }
        $this->nameTemplate = $nameTemplate;
        $this->fileHeader = $header;
        $this->fileFooter = $footer;
        $this->maxStrings = $maxStrings;
        $this->maxLength = $maxLength;
        $this->chunkSize = $chunkSize;
        $this->directoryDelimiter = $directoryDelimiter;
        $this->gzip = $gzip;
        $this->headerSize = strlen($this->fileHeader); //нужно в байтах
        $this->footerSize = strlen($this->fileFooter); //нужно в байтах
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
            if ($this->currentFileSizeStrings >= $this->maxStrings) {
                $this->finalizeFile();
                continue;
            }
            $str = $this->strings[$idx];
            $nextLen = strlen($str) + $this->footerSize; //in bytes
            if (($this->headerSize + $nextLen) >= $this->maxLength) {
                throw new LargeArrayWriterException("Result string too long (length with footer $nextLen bytes) : $str", LargeArrayWriterException::ERROR_STRING_TO_LONG);
            }
            if (($this->currentFileSizeMb + $nextLen) >= $this->maxLength) {
                $this->finalizeFile();
                continue;
            }
            $this->filePuts($this->currentFile, $str);
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
        $fileName = str_replace('%NUM%', $this->currentNum, $this->nameTemplate);
        if ($this->gzip) {
            $fileName .= ".gz";
        }
        $this->writtenFiles[] = $fileName;
        $fullName = $this->path . $this->directoryDelimiter . $fileName;
        $this->currentFile = $this->fileOpen($fullName);
        if (empty($this->currentFile)) {
            throw new LargeArrayWriterException("Can't open file $fullName for write!", LargeArrayWriterException::ERROR_CANT_OPEN_FILE);
        }
        $this->filePuts($this->currentFile, $this->fileHeader);
        $this->currentFileSizeMb = $this->headerSize; //in bytes
    }

    protected function finalizeFile()
    {
        $this->filePuts($this->currentFile, $this->fileFooter);
        $this->fileClose($this->currentFile);
        ++$this->currentNum;
        $this->currentFileSizeMb = $this->currentFileSizeStrings = 0;
        $this->currentFile = null;
    }

    protected function fileOpen(string $fullName)
    {
        if ($this->gzip) {
            return gzopen($fullName, "w");
        } else {
            return fopen($fullName, "w");
        }
    }

    protected function filePuts($file, string $string)
    {
        if ($this->gzip) {
            gzwrite($file, $string);
        } else {
            fwrite($file, $string);
        }
    }

    protected function fileClose($file)
    {
        if ($this->gzip) {
            gzclose($file);
        } else {
            fclose($file);
        }
    }


    /**
     * @param string $dir
     * @return bool
     */
    protected function ensureDirectory(string $dir) : bool
    {
        $names = explode($this->directoryDelimiter, $dir);
        $path = '';
        foreach ($names as $part) {
            if (strlen($part) == 0) {
                continue;
            }

            $path .= $this->directoryDelimiter . $part;
            if (is_dir($path)) {
                continue;
            }

            $umask = umask(0);
            $success = mkdir($path, 0777, true);
            umask($umask);
            if (!$success) {
                return false;
            }
        }
        return true;
    }


    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return LargeArrayWriter
     */
    public function setPath(string $path) : self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameTemplate() : string
    {
        return $this->nameTemplate;
    }

    /**
     * @param string $nameTemplate
     * @return LargeArrayWriter
     */
    public function setNameTemplate(string $nameTemplate) : self
    {
        $this->nameTemplate = $nameTemplate;
        return $this;
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
     * @return LargeArrayWriter
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
     * @return LargeArrayWriter
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
     * @return LargeArrayWriter
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
     * @return LargeArrayWriter
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
     * @return LargeArrayWriter
     */
    public function setChunkSize(int $chunkSize) : self
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }
}
