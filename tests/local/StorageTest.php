<?php

namespace yii2tech\tests\unit\filestorage\local;

use Yii;
use yii2tech\filestorage\local\Storage;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * Test case for the extension [[Storage]].
 * @see Storage
 *
 * @group local
 */
class StorageTest extends TestCase
{
    public function testSetGet()
    {
        $fileStorage = new Storage();

        $testBasePath = '/test/base/path';
        $fileStorage->setBasePath($testBasePath);
        $this->assertEquals($fileStorage->getBasePath(), $testBasePath, 'Unable to set base path correctly!');

        $testBaseUrl = 'http://test/base/url';
        $fileStorage->setBaseUrl($testBaseUrl);
        $this->assertEquals($fileStorage->getBaseUrl(), $testBaseUrl, 'Unable to set base URL correctly!');
    }
}
