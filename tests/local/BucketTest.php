<?php

namespace yii2tech\tests\unit\filestorage\local;

use Yii;
use yii\helpers\FileHelper;
use yii2tech\filestorage\local\Storage;
use yii2tech\filestorage\local\Bucket;
use yii2tech\tests\unit\filestorage\BucketTestTrait;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * @group local
 *
 * @method Storage createFileStorage(array $config = [])
 * @method Bucket createFileStorageBucket(array $config = [])
 */
class BucketTest extends TestCase
{
    use BucketTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storageClass = Storage::className();
        $this->bucketClass = Bucket::className();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        FileHelper::removeDirectory($this->getTestBasePath());

        parent::tearDown();
    }

    /**
     * Returns the test file storage base path.
     * @return string file storage base path.
     */
    protected function getTestBasePath()
    {
        return Yii::getAlias('@yii2tech/tests/unit/filestorage/runtime') . DIRECTORY_SEPARATOR . 'test_file_storage';
    }

    /**
     * @return array
     */
    protected function defaultFileStorageConfig()
    {
        return [
            'basePath' => $this->getTestBasePath(),
            'baseUrl' => 'http://test/base/url',
            'filePermission' => 0777
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

    /**
     * @depends testSetGet
     */
    public function testGetDefaultBaseSubPath()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_bucket_name';
        $bucket->setName($testBucketName);

        $defaultBaseSubPath = $bucket->getBaseSubPath();
        $this->assertEquals($testBucketName, $defaultBaseSubPath, 'Default base sub path has incorrect value!' );
    }

    /**
     * @depends testSetGet
     */
    public function testResolveFileSubDirTemplate()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_bucket_name';
        $bucket->setName($testBucketName);

        $bucket->fileSubDirTemplate = '{ext}/{^name}/{^^name}';

        $testFileSelfName = 'test_file_self_name';
        $testFileExtension = 'tmp';
        $testFileName = $testFileSelfName.'.'.$testFileExtension;

        $returnedFullFileName = $bucket->getFullFileName($testFileName);

        $expectedFullFileName = $bucket->getStorage()->getBasePath() . DIRECTORY_SEPARATOR;
        $expectedFullFileName .= $bucket->getBaseSubPath() . DIRECTORY_SEPARATOR;
        $expectedFullFileName .= $testFileExtension . DIRECTORY_SEPARATOR;
        $expectedFullFileName .= substr($testFileName, 0, 1) . DIRECTORY_SEPARATOR;
        $expectedFullFileName .= substr($testFileName, 1, 1) . DIRECTORY_SEPARATOR;
        $expectedFullFileName .= $testFileName;

        $this->assertEquals($expectedFullFileName, $returnedFullFileName, 'Unable to resolve file sub dir correctly!');
    }

    /**
     * {@inheritdoc}
     */
    public function testCreateBucket()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_bucket_name';
        $bucket->setName($testBucketName);

        $this->assertTrue($bucket->create(), 'Unable to create bucket!');

        $bucketFullBasePath = $bucket->getFullBasePath();
        $this->assertTrue(file_exists($bucketFullBasePath) && is_dir($bucketFullBasePath) , 'Unable to create bucket full path directory!');
    }

    /**
     * {@inheritdoc}
     */
    public function testBucketDestroy()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_destroy_bucket';
        $bucket->setName($testBucketName);
        $bucket->create();

        $this->assertTrue($bucket->destroy(), 'Unable to destroy bucket!');

        $bucketFullBasePath = $bucket->getFullBasePath();
        $this->assertFalse(file_exists($bucketFullBasePath), 'Unable to destroy bucket full path directory!');
    }


    /**
     * {@inheritdoc}
     */
    public function testGetFileUrl()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_get_file_url_bucket';
        $bucket->setName($testBucketName);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $returnedFileUrl = $bucket->getFileUrl($testFileName);
        $this->assertTrue(!empty($returnedFileUrl), 'File URL is empty!');

        $bucketFileName = $bucket->getFullFileName($testFileName);

        $fileSubName = str_replace($bucket->getStorage()->getBasePath(), '', $bucketFileName);

        $expectedFileUrl = $bucket->getStorage()->getBaseUrl() . $fileSubName;
        $this->assertEquals($expectedFileUrl, $returnedFileUrl, 'Wrong file URL returned!');
    }
}