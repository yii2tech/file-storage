<?php

namespace yii2tech\tests\unit\filestorage\sftp;

use yii2tech\tests\unit\filestorage\TestCase;

/**
 * @group sftp
 */
class ConnectionTest extends TestCase
{
    public function testOpen()
    {
        $ssh = $this->getSsh();

        $ssh->open();

        $this->assertNotEmpty($ssh->getSession());
        $this->assertTrue($ssh->getIsActive());
    }

    /**
     * @depends testOpen
     */
    public function testClose()
    {
        $ssh = $this->getSsh();

        $ssh->open();
        $ssh->close();

        $this->assertFalse($ssh->getIsActive());
    }

    /**
     * @depends testOpen
     */
    public function testExecute()
    {
        $ssh = $this->getSsh();

        $output = $ssh->execute('whoami');

        $this->assertEquals($ssh->username, trim($output, "\n\r"));
    }
}