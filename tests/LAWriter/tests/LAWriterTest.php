<?php
namespace satmaelstorm\LargeArrayWriter\tests;

use satmaelstorm\LargeArrayWriter\LAWriter;
use satmaelstorm\LargeArrayWriter\NameIterator\FileNumerator;
use satmaelstorm\LargeArrayWriter\Saver\InMemory;

class LAWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWriteSmallFile()
    {
        $saver = new InMemory();
        $writer = new LAWriter(
            new FileNumerator(),
            $saver,
            "Header\n",
            "Footer"
        );
        $writer->addString("string1\n");
        $fileList = $writer->finalize();

        $this->assertTrue(1 == $writer->count());
        $this->assertTrue(1 == count($writer));
        $this->assertEquals(["/tmp/file_0.txt"], $fileList);
        $this->assertEquals("Header\nstring1\nFooter", $saver->getContent());
    }

    public function testWriteTwoSmallFiles()
    {
        $saver = new InMemory();
        $writer = new LAWriter(
            new FileNumerator(),
            $saver,
            "Header\n",
            "Footer",
            1
        );
        $writer->addString("string1\n");
        $writer->addString("string2\n");
        $fileList = $writer->finalize();

        $this->assertTrue(2 == $writer->count());
        $this->assertTrue(2 == count($writer));
        $this->assertEquals(["/tmp/file_0.txt", "/tmp/file_1.txt"], $fileList);
        $this->assertEquals("Header\nstring2\nFooter", $saver->getContent());
        $this->assertEquals("Header\nstring1\nFooter", $saver->getContent("/tmp/file_0.txt"));
    }

    public function testWriteTwoSmallFilesByLength()
    {
        $saver = new InMemory();
        $writer = new LAWriter(
            new FileNumerator(),
            $saver,
            "Header\n",
            "Footer",
            -1,
            22
        );

        $writer->addString("string1\n");
        $writer->addString("string2\n");
        $fileList = $writer->finalize();

        $this->assertTrue(2 == $writer->count());
        $this->assertTrue(2 == count($writer));
        $this->assertEquals(["/tmp/file_0.txt", "/tmp/file_1.txt"], $fileList);
        $this->assertEquals("Header\nstring2\nFooter", $saver->getContent());
        $this->assertEquals("Header\nstring1\nFooter", $saver->getContent("/tmp/file_0.txt"));
    }

    public function testWriteWithChunk()
    {
        $saver = new InMemory();
        $writer = new LAWriter(
            new FileNumerator(),
            $saver,
            "Header\n",
            "Footer",
            -1,
            -1,
            2
        );

        $writer->addString("string1\n");
        $writer->addString("string2\n");
        $writer->addString("string3\n");
        $this->assertEquals("Header\nstring1\nstring2\n", $saver->getContent());
        $writer->addString("string4\n");
        $this->assertEquals("Header\nstring1\nstring2\nstring3\nstring4\n", $saver->getContent());
        $writer->addString("string5\n");
        $this->assertEquals("Header\nstring1\nstring2\nstring3\nstring4\n", $saver->getContent());
        $writer->finalize();
        $this->assertEquals("Header\nstring1\nstring2\nstring3\nstring4\nstring5\nFooter", $saver->getContent());
    }
}