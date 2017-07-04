<?php

namespace yii2tech\tests\unit\filestorage\amazon;

use yii2tech\filestorage\amazon\Storage;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * Test case for [[Storage]]
 * @see Storage
 *
 * @group amazon
 */
class StorageTest extends TestCase
{
    /**
     * Returns the test AWS key.
     * @return string AWS key.
     */
    protected function getAwsKey()
    {
        return defined('AWS_KEY') ? AWS_KEY : '???';
    }

    /**
     * Returns the test AWS secret key.
     * @return string AWS secret key.
     */
    protected function getAwsSecretKey()
    {
        return defined('AWS_SECRET_KEY') ? AWS_SECRET_KEY : '???';
    }

    /**
     * Creates test file storage.
     * @return Storage file storage instance.
     */
    protected function createFileStorage()
    {
        return new Storage([
            'awsKey' => $this->getAwsKey(),
            'awsSecretKey' => $this->getAwsSecretKey()
        ]);
    }

    // Tests:

    public function testSetGet()
    {
        $fileStorage = $this->createFileStorage();
        
        $testAmazonS3 = new \stdClass();
        $fileStorage->setAmazonS3($testAmazonS3);
        $this->assertEquals($fileStorage->getAmazonS3(), $testAmazonS3, 'Unable to set amazon S3 object correctly!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetDefaultAmazonS3()
    {
        $fileStorage = $this->createFileStorage();
        
        $defaultAmazonS3 = $fileStorage->getAmazonS3();
        $this->assertTrue(is_object($defaultAmazonS3), 'Unable to get default amazon S3 object!');

        $this->assertEquals($fileStorage->awsKey, $defaultAmazonS3->getCredentials()->getAccessKeyId(), 'Unable to pass AWS key!');
        $this->assertEquals($fileStorage->awsSecretKey, $defaultAmazonS3->getCredentials()->getSecretKey(), 'Unable to pass AWS secret key!');
    }

    /**
     * @depends testGetDefaultAmazonS3
     */
    public function testAmazonS3Config()
    {
        $fileStorage = $this->createFileStorage();
        $fileStorage->amazonS3Config = [
            'region' => 'eu-central-1',
        ];

        $amazonS3 = $fileStorage->getAmazonS3();
        $this->assertEquals($fileStorage->amazonS3Config['region'], $amazonS3->getRegion());
    }
}
