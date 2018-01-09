<?php

namespace HttpTest\Tests\Integration;

use HttpTest\ServerSwitch;
use PHPUnit_Framework_TestCase;

final class ServerSwitchTest extends PHPUnit_Framework_TestCase
{
    public function testServerSwitch()
    {
        $switch = ServerSwitch::create();
        $this->assertFalse($switch->isOn());

        $switch->on();
        $this->assertTrue($switch->isOn());

        $switch->off();
        $this->assertFalse($switch->isOn());
    }
}
