<?php

namespace yii2tech\tests\unit\filestorage\mongodb;

use yii2tech\filestorage\mongodb\Storage;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * @group mongodb
 */
class BucketTest extends TestCase
{
    /**
     * Creates file storage.
     * @return Storage file storage instance
     */
    protected function createFileStorage()
    {
        return new Storage([
            'db' => $this->getMongodb(),
        ]);
    }

    // Tests :

    public function testGetCollection()
    {
        ;
    }
}