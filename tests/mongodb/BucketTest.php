<?php

namespace yii2tech\tests\unit\filestorage\mongodb;

use yii\mongodb\file\Collection;
use yii2tech\filestorage\mongodb\Storage;
use yii2tech\filestorage\mongodb\Bucket;
use yii2tech\tests\unit\filestorage\BucketTestTrait;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * @group mongodb
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

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $database = $this->getMongodb()->getDatabase();

        $rows = $database->listCollections([
            'name' => ['$regex' => '^test.*\.(chunks|files)']
        ]);

        foreach ($rows as $row) {
            $database->dropCollection($row['name']);
        }

        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function defaultFileStorageConfig()
    {
        return [
            'db' => $this->getMongodb(),
        ];
    }

    // Tests :

    public function testGetCollection()
    {
        $bucket = $this->createFileStorageBucket([
            'name' => 'name',
            'collectionPrefix' => 'test_prefix',
        ]);

        $collection = $bucket->getCollection();
        $this->assertTrue($collection instanceof Collection);
        $this->assertEquals('test_prefix', $collection->prefix);
    }
}