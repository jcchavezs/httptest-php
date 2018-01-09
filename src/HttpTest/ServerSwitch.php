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
     * @return ServerSwitch
     */
    public static function create()
    {
        return new self(self::buildWitnessFilename());
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    public function on()
    {
        if (false === touch($this->filename)) {
            throw new RuntimeException('Server could not be turned on');
        }
    }

    /**
     * @return bool
     */
    public function isOn()
    {
        return file_exists($this->filename);
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    public function off()
    {
        if (false === unlink($this->filename)) {
            throw new RuntimeException('Server could not be turned on');
        }
    }

    private static function buildWitnessFilename()
    {
        return sys_get_temp_dir() . '/' . uniqid('', true);
    }
}
