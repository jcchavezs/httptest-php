<?php

namespace HttpTest;

final class Key
{
    private $filename;

    private function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return Key
     */
    public static function create()
    {
        return new self(self::buildWitnessFilename());
    }

    private static function buildWitnessFilename()
    {
        return sys_get_temp_dir() . '/' . uniqid('', true);
    }

    /**
     * @return void
     */
    public function on()
    {
        file_put_contents($this->filename, '');
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
     */
    public function off()
    {
        unlink($this->filename);
    }
}
