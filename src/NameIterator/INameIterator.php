<?php
/**
 * Created by PhpStorm.
 * User: maelstorm
 * Date: 08.06.18
 * Time: 18:35
 */
namespace satmaelstorm\LargeArrayWriter\NameIterator;

interface INameIterator
{
    public function next(): string;
}