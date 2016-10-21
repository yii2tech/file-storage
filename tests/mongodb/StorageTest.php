<?php

namespace yii2tech\tests\unit\filestorage\mongodb;

use yii\mongodb\Connection;
use yii2tech\filestorage\mongodb\Storage;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * @group mongodb
 */
class StorageTest extends TestCase
{
    public function testInitConnection()
    {
        $storage = new Storage([
            'db' => [
                'class' => Connection::className()
            ]
        ]);
        $this->assertTrue($storage->db instanceof Connection);

        $mongodb = $this->getMongodb(false, false);
        $storage = new Storage([
            'db' => $mongodb
        ]);
        $this->assertSame($mongodb, $storage->db);
    }
}