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
        $sftp = $this->getSsh();

        $sftp->open();

        $this->assertNotEmpty($sftp->getSession());
        $this->assertTrue($sftp->getIsActive());
    }

    /**
     * @depends testOpen
     */
    public function testClose()
    {
        $sftp = $this->getSsh();

        $sftp->open();
        $sftp->close();

        $this->assertFalse($sftp->getIsActive());
    }
}