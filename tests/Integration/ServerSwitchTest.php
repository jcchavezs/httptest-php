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

        $switch->on();
        $this->assertFalse($switch->isOff());
    }
}
