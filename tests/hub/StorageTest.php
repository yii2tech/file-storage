<?php

namespace yii2tech\tests\unit\filestorage\hub;

use Yii;
use yii2tech\filestorage\hub\Storage;
use yii2tech\filestorage\local\Bucket;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * Test case for the extension [[Storage]].
 * @see Storage
 *
 * @group hub
 */
class StorageTest extends TestCase
{
    /**
     * @return Storage storage instance.
     */
    protected function createFileStorage()
    {
        $methodsList = [
            'init',
        ];
        $fileStorage = $this->getMock(\yii2tech\filestorage\local\Storage::className(), $methodsList);
        return $fileStorage;
    }

    /**
     * @return Bucket bucket instance.
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
        ];
        $bucket = $this->getMock(Bucket::className(), $methodsList);
        return $bucket;
    }

    protected function createFilledFileStorageHub($maxStorageCount = 3, $maxBucketCount = 5, $storageNamePrefix = 'test_storage_', $bucketNamePrefix = 'test_bucket_')
    {
        $fileStorageHub = new Storage();

        $testStorage = $this->createFileStorage();
        $testStorageClassName = get_class($testStorage);

        $testBucket = $this->createFileStorageBucket();
        $testBucketClassName = get_class($testBucket);

        $testStorages = [];
        for ($storageCount = 1; $storageCount <= $maxStorageCount; $storageCount++) {
            $testBuckets = [];
            for ($bucketCount = 1; $bucketCount <= $maxBucketCount; $bucketCount++) {
                $testBucketName = $bucketNamePrefix . '_' . $storageCount . '_' . $bucketCount;
                $testBuckets[$testBucketName] = [
                    'class' => $testBucketClassName
                ];
            }
            $testStorage = [
                'class'=> $testStorageClassName,
                'buckets' => $testBuckets
            ];

            $testStorageName = $storageNamePrefix . '_' . $storageCount;
            $testStorages[$testStorageName] = $testStorage;
        }
        $fileStorageHub->setStorages($testStorages);
        return $fileStorageHub;
    }

    // Tests :

    public function testAddStorage()
    {
        $fileStorageHub = new Storage();

        $testStorageName = 'testStorageName';
        $testStorage = $this->createFileStorage();

        $this->assertTrue($fileStorageHub->addStorage($testStorageName, $testStorage), 'Unable to add storage object!');

        $returnedStorage = $fileStorageHub->getStorage($testStorageName);
        $this->assertTrue(is_object($returnedStorage), 'Unable to get added storage!');
    }

    /**
     * @depends testAddStorage
     */
    public function testAddStorageAsConfig()
    {
        $fileStorageHub = new Storage();

        $testStorage = $this->createFileStorage();
        $testStorageClassName = get_class($testStorage);

        $testStorageName = 'test_storage_name';
        $testStorageConfig = [
            'class' => $testStorageClassName
        ];
        $this->assertTrue($fileStorageHub->addStorage($testStorageName, $testStorageConfig), 'Unable to add storage as config!');

        $returnedStorage = $fileStorageHub->getStorage($testStorageName);
        $this->assertTrue(is_object($returnedStorage), 'Unable to get storage added by config!');
        $this->assertEquals($testStorageClassName, get_class($returnedStorage), 'Added by config storage has wrong class name!');
    }

    /**
     * @depends testAddStorage
     */
    public function testSetStorages()
    {
        $fileStorageHub = new Storage();

        $storagesCount = 5;
        $testStorages = [];
        for ($i = 1; $i <= $storagesCount; $i++) {
            $testStorageName = 'testStorageName' . $i;
            $testStorage = $this->createFileStorage();
            $testStorages[$testStorageName] = $testStorage;
        }

        $this->assertTrue($fileStorageHub->setStorages($testStorages), 'Unable to set storages list!');
        $returnedStorages = $fileStorageHub->getStorages();
        $this->assertEquals(count($returnedStorages), count($testStorages), 'Wrong count of the set storages!');
    }

    /**
     * @depends testAddStorage
     */
    public function testHasStorage()
    {
        $fileStorageHub = new Storage();

        $testStorageName = 'test_storage_name';
        $this->assertFalse($fileStorageHub->hasStorage($testStorageName), 'Not added storage present in the storage!');

        $testStorage = $this->createFileStorage();
        $fileStorageHub->addStorage($testStorageName, $testStorage);
        $this->assertTrue($fileStorageHub->hasStorage($testStorageName), 'Added storage does not present in the storage!');
    }

    /**
     * @depends testSetStorages
     */
    public function testAddBucket()
    {
        $fileStorageHub = new Storage();

        $testStorageName = 'test_storage';
        $testStorage = $this->createFileStorage();
        $fileStorageHub->addStorage($testStorageName, $testStorage);

        $testBucketName = 'testBucketName';
        $testBucket = $this->createFileStorageBucket();

        $this->assertTrue($fileStorageHub->addBucket($testBucketName, $testBucket), 'Unable to add bucket object to the hub!');

        $returnedBucket = $fileStorageHub->getBucket($testBucketName);
        $this->assertEquals($testBucketName, $returnedBucket->getName(), 'Added bucket has wrong name!');
    }

    /**
     * @depends testAddBucket
     */
    public function testSetBuckets()
    {
        $fileStorageHub = new Storage();

        $testStorageName = 'test_storage';
        $testStorage = $this->createFileStorage();
        $fileStorageHub->addStorage($testStorageName, $testStorage);

        $bucketsCount = 5;
        $testBuckets = [];
        for ($i = 1; $i <= $bucketsCount; $i++) {
            $testBucketName = 'testBucketName' . $i;
            $testBucket = $this->createFileStorageBucket();
            $testBuckets[$testBucketName] = $testBucket;
        }

        $this->assertTrue($fileStorageHub->setBuckets($testBuckets), 'Unable to set buckets list!');
        $returnedBuckets = $fileStorageHub->getBuckets();
        $this->assertEquals(count($returnedBuckets), count($testBuckets), 'Wrong count of the set buckets!');
    }

    /**
     * @depends testSetBuckets
     */
    public function testGetBucketsFromDifferentStorages()
    {
        $maxStorageCount = 3;
        $maxBucketCount = 5;
        $storageNamePrefix = 'test_storage';
        $bucketNamePrefix = 'test_bucket';
        $fileStorageHub = $this->createFilledFileStorageHub($maxStorageCount, $maxBucketCount, $storageNamePrefix, $bucketNamePrefix);

        $returnBuckets = $fileStorageHub->getBuckets();

        $this->assertEquals($maxStorageCount*$maxBucketCount, count($returnBuckets), 'Wrong count of returned buckets!');
    }

    /**
     * @depends testGetBucketsFromDifferentStorages
     */
    public function testHasBucket()
    {
        $maxStorageCount = 3;
        $maxBucketCount = 5;
        $storageNamePrefix = 'test_storage';
        $bucketNamePrefix = 'test_bucket';
        $fileStorageHub = $this->createFilledFileStorageHub($maxStorageCount, $maxBucketCount, $storageNamePrefix, $bucketNamePrefix);

        $testBucketName = $bucketNamePrefix.'_'.rand(1,$maxStorageCount).'_'.rand(1, $maxBucketCount);
        $this->assertTrue($fileStorageHub->hasBucket($testBucketName), 'Unable to determine bucket existance!');

        $testUnexistingBucketName = 'unexisting_bucket_name';
        $this->assertFalse($fileStorageHub->hasBucket($testUnexistingBucketName), 'Unexisting bucket reported to be present!');
    }

    /**
     * @depends testHasBucket
     */
    public function testGetBucketFromDifferentStorages()
    {
        $maxStorageCount = 3;
        $maxBucketCount = 5;
        $storageNamePrefix = 'test_storage';
        $bucketNamePrefix = 'test_bucket';
        $fileStorageHub = $this->createFilledFileStorageHub($maxStorageCount, $maxBucketCount, $storageNamePrefix, $bucketNamePrefix);

        $testBucketName = $bucketNamePrefix . '_' . rand(1, $maxStorageCount) . '_' . rand(1, $maxBucketCount);
        $returnedBucket = $fileStorageHub->getBucket($testBucketName);

        $this->assertTrue(is_object($returnedBucket), 'Unable to get bucket from complex hub!');
    }
}
