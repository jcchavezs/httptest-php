<?php

namespace HttpTest\Tests\Integration;

use HttpTest\ServerSwitch;
use PHPUnit_Framework_TestCase;

final class ServerSwitchTest extends PHPUnit_Framework_TestCase
{
    public function testServerSwitch()
    {
        $switch = ServerSwitch::create();
        $this->assertFalse($switch->isOff());

        $switch->off();
        $this->assertTrue($switch->isOff());

        $switch->reset();
        $this->assertFalse($switch->isOff());
    }

    public function testServerSwitchDeletesWitnessFileOnDestruct()
    {
        $filename = sprintf('switch-%s.test', uniqid());
        $switch = ServerSwitch::create($filename);
        $switch->off();
        $this->assertFileExists($filename);
        unset($switch);
        $this->assertFileNotExists($filename);
    }
}
