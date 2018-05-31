<?php

namespace HttpTest;

use RuntimeException;

final class ServerSwitch
{
    /**
     * @var string
     */
    private $filename;

    private function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param string|null $filename
     * @return ServerSwitch
     */
    public static function create($filename = null)
    {
        return new self($filename ?: self::buildWitnessFilename());
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    public function off()
    {
        if (false === touch($this->filename)) {
            throw new RuntimeException('Server could not be turned on');
        }
    }

    /**
     * @return bool
     */
    public function isOff()
    {
        return file_exists($this->filename);
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    public function on()
    {
        if (false === unlink($this->filename)) {
            throw new RuntimeException('Server could not be turned on');
        }
    }

    private static function buildWitnessFilename()
    {
        return sys_get_temp_dir() . '/' . uniqid('', true);
    }

    public function __destruct()
    {
        @unlink($this->filename);
    }
}
