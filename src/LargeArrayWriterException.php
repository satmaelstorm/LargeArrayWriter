<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 19.05.2018
 * Time: 18:31
 */

namespace satmaelstorm\LargeArrayWriter;


class LargeArrayWriterException extends \Exception
{
    const ERROR_STRING_TO_LONG = 1;
    const ERROR_CANT_OPEN_FILE = 2;
}