<?php

namespace yii2tech\tests\unit\filestorage;

use yii2tech\filestorage\BaseBucket;

/**
 * Test case for the extension [[BaseBucket]].
 * @see BaseBucket
 */
class BaseBucketTest extends TestCase
{
    /**
     * @return BaseBucket bucket instance.
     */
    protected function createFileStorageBucket()
    {
        $methodsList = [
            'create',
            'destroy',
            'exists',
            'saveFileContent',
            'getFileContent',
            'deleteFile',
            'fileExists',
            'copyFileIn',
            'copyFileOut',
            'copyFileInternal',
            'moveFileIn',
            'moveFileOut',
            'moveFileInternal',
            'getFileUrl',
            'openFile',
        ];
        $bucket = $this->getMockBuilder('yii2tech\filestorage\BaseBucket')
            ->setMethods($methodsList)
            ->getMock();
        return $bucket;
    }

    // Tests :

    public function testSetGet()
    {
        $bucket = $this->createFileStorageBucket();

        $testName = 'test_bucket_name';
        $this->assertTrue($bucket->setName($testName), 'Unable to set name!');
        $this->assertEquals($bucket->getName(), $testName, 'Unable to set name! correctly');

        $testStorage = $this->getMockBuilder('yii2tech\filestorage\BaseStorage')->getMock();
        $this->assertTrue($bucket->setStorage($testStorage), 'Unable to set storage!');
        $this->assertEquals($bucket->getStorage(), $testStorage, 'Unable to set storage correctly!');
    }
}
