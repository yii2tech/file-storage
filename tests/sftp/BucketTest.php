<?php

namespace yii2tech\tests\unit\filestorage\sftp;

use yii2tech\filestorage\sftp\Bucket;
use yii2tech\filestorage\sftp\Storage;
use yii2tech\tests\unit\filestorage\BucketTestTrait;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * @group sftp
 *
 * @method Storage createFileStorage(array $config = [])
 * @method Bucket createFileStorageBucket(array $config = [])
 */
class BucketTest extends TestCase
{
    use BucketTestTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->storageClass = Storage::className();
        $this->bucketClass = Bucket::className();

        parent::setUp();
    }

    protected function tearDown()
    {
        $storage = $this->createFileStorage();
        ssh2_sftp_rmdir($storage->getSftp(), $storage->basePath);

        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function defaultFileStorageConfig()
    {
        return [
            'ssh' => $this->getSsh(),
            'baseUrl' => 'http://test/base/url',
            //'filePermission' => 0777
        ];
    }

    // Tests:

    public function testSetGet()
    {
        $bucket = $this->createFileStorageBucket();

        $testBaseSubPath = 'test/base/sub/path';
        $bucket->setBaseSubPath($testBaseSubPath);
        $this->assertEquals($bucket->getBaseSubPath(), $testBaseSubPath, 'Unable to set base sub path correctly!');
    }
}