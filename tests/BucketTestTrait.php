<?php

namespace yii2tech\tests\unit\filestorage;

use Yii;
use yii2tech\filestorage\BaseBucket;
use yii2tech\filestorage\BaseStorage;

trait BucketTestTrait
{
    /* @var $this TestCase */

    /**
     * @var string
     */
    protected $storageClass;
    /**
     * @var string
     */
    protected $bucketClass;

    /**
     * Creates file storage.
     * @param array $config
     * @return BaseStorage file storage instance
     */
    protected function createFileStorage(array $config = [])
    {
        $config = array_merge(
            [
                'class' => $this->storageClass
            ],
            method_exists($this, 'defaultFileStorageConfig') ? $this->defaultFileStorageConfig() : [],
            $config
        );
        return Yii::createObject($config);
    }

    /**
     * Creates new file storage bucket.
     * @param array $config
     * @return BaseBucket file storage bucket instance
     */
    protected function createFileStorageBucket(array $config = [])
    {
        $config = array_merge(
            [
                'class' => $this->bucketClass
            ],
            method_exists($this, 'defaultFileStorageBucketConfig') ? $this->defaultFileStorageBucketConfig() : [],
            $config
        );
        if (!isset($config['storage'])) {
            $config['storage'] = $this->createFileStorage();
        }
        return Yii::createObject($config);
    }

    // Tests :

    public function testCreateBucket()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_bucket_name']);

        $this->assertTrue($bucket->create(), 'Unable to create bucket!');
    }

    /**
     * @depends testCreateBucket
     */
    public function testDestroyBucket()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_destroy_bucket']);
        $bucket->create();

        $this->assertTrue($bucket->destroy(), 'Unable to destroy bucket!');
    }

    /**
     * @depends testDestroyBucket
     */
    public function testBucketExists()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_exists_bucket']);

        $this->assertFalse($bucket->exists(), 'Not yet created bucket exists!');

        $bucket->create();
        $this->assertTrue($bucket->exists(), 'Created bucket does not exists!');

        $bucket->destroy();
        $this->assertFalse($bucket->exists(), 'Destroyed bucket exists!');
    }

    public function testSaveFileContent()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_save_file_content_bucket']);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $this->assertTrue($bucket->saveFileContent($testFileName, $testFileContent), 'Unable to save file content!');
    }

    /**
     * @depends testSaveFileContent
     */
    public function testGetFileContent()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_get_file_content_bucket']);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $returnedFileContent = $bucket->getFileContent($testFileName);

        $this->assertEquals($testFileContent, $returnedFileContent, 'Unable to get file content!');
    }

    /**
     * @depends testSaveFileContent
     */
    public function testDeleteFile()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_delete_file_bucket';
        $bucket->setName($testBucketName);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $this->assertTrue($bucket->deleteFile($testFileName), 'Unable to delete file!');
    }

    /**
     * @depends testDeleteFile
     */
    public function testFileExists()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_exists_file_bucket';
        $bucket->setName($testBucketName);

        $testFileName = 'test_file_name.tmp';

        $this->assertFalse($bucket->fileExists($testFileName), 'Not saved yet file exists!');

        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);
        $this->assertTrue($bucket->fileExists($testFileName), 'Saved file does not exist!');

        $bucket->deleteFile($testFileName);
        $this->assertFalse($bucket->fileExists($testFileName), 'Deleted file exists!');
    }

    /**
     * @depends testFileExists
     */
    public function testCopyFileIn()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_copy_file_in_bucket';
        $bucket->setName($testBucketName);

        $testSrcFileSelfName = 'test_src_file.tmp';
        $testFileContent = 'Test file content';
        $tmpPath = $this->getTestTmpPath();
        $testSrcFileName = $tmpPath . DIRECTORY_SEPARATOR . $testSrcFileSelfName;
        file_put_contents($testSrcFileName, $testFileContent);

        $testBucketFileName = 'test_bucket_file_name.tmp';

        $this->assertTrue($bucket->copyFileIn($testSrcFileName, $testBucketFileName), 'Unable to copy file into the bucket!');

        $returnedFileContent = $bucket->getFileContent($testBucketFileName);
        $this->assertEquals($testFileContent, $returnedFileContent, 'Unable to get copied file content!');
    }

    /**
     * @depends testFileExists
     */
    public function testCopyFileOut()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_copy_file_out_bucket';
        $bucket->setName($testBucketName);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $testDestFileSelfName = 'test_dest_file.tmp';
        $tmpPath = $this->getTestTmpPath();
        $testDestFileName = $tmpPath . DIRECTORY_SEPARATOR . $testDestFileSelfName;

        $this->assertTrue($bucket->copyFileOut($testFileName, $testDestFileName), 'Unable to copy file out from the bucket!');
        $this->assertTrue(file_exists($testDestFileName), 'Destination file has not been created!');
        $this->assertEquals($testFileContent, file_get_contents($testDestFileName), 'Destination file has wrong content!');
    }

    /**
     * @depends testCopyFileIn
     */
    public function testMoveFileIn()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_move_file_in_bucket';
        $bucket->setName($testBucketName);

        $testSrcFileSelfName = 'test_src_file.tmp';
        $testFileContent = 'Test file content';
        $tmpPath = $this->getTestTmpPath();
        $testSrcFileName = $tmpPath . DIRECTORY_SEPARATOR . $testSrcFileSelfName;
        file_put_contents($testSrcFileName, $testFileContent);

        $testBucketFileName = 'test_bucket_file_name.tmp';

        $this->assertTrue($bucket->moveFileIn($testSrcFileName, $testBucketFileName), 'Unable to move file into the bucket!');
        $this->assertFalse(file_exists($testSrcFileName), 'Source file has not been deleted!');

        $returnedFileContent = $bucket->getFileContent($testBucketFileName);
        $this->assertEquals($testFileContent, $returnedFileContent, 'Unable to get moved file content!');
    }

    /**
     * @depends testCopyFileOut
     */
    public function testMoveFileOut()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_move_file_out_bucket';
        $bucket->setName($testBucketName);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $testDestFileSelfName = 'test_dest_file.tmp';
        $tmpPath = $this->getTestTmpPath();
        $testDestFileName = $tmpPath . DIRECTORY_SEPARATOR . $testDestFileSelfName;

        $this->assertTrue($bucket->moveFileOut($testFileName, $testDestFileName), 'Unable to move file out from the bucket!');
        $this->assertTrue(file_exists($testDestFileName), 'Destination file has not been created!');
        $this->assertEquals($testFileContent, file_get_contents($testDestFileName), 'Destination file has wrong content!');
        $this->assertFalse($bucket->fileExists($testFileName), 'Source file has not been deleted!');
    }

    /**
     * @depends testCopyFileIn
     */
    public function testCopyFileInternalSameBucket()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_copy_file_internal_bucket';
        $bucket->setName($testBucketName);

        $testSrcFileName = 'test_src_file.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testSrcFileName,$testFileContent);

        $testDestFileName = 'test_dest_file.tmp';
        $this->assertTrue($bucket->copyFileInternal($testSrcFileName,$testDestFileName), 'Unable to copy file internally in the same bucket!');
        $this->assertTrue($bucket->fileExists($testDestFileName), 'Unable to create destination file!');
        $this->assertEquals($testFileContent, $bucket->getFileContent($testDestFileName), 'Destination file has wrong content!');
    }

    /**
     * @depends testCopyFileInternalSameBucket
     */
    public function testCopyFileInternalDifferentBuckets()
    {
        $fileStorage = $this->createFileStorage();
        $testSrcBucketName = 'test_copy_file_internal_src_bucket';
        $testDestBucketName = 'test_copy_file_internal_dest_bucket';
        $buckets = [
            $testSrcBucketName,
            $testDestBucketName
        ];
        $fileStorage->setBuckets($buckets);

        $srcBucket = $fileStorage->getBucket($testSrcBucketName);
        $destBucket = $fileStorage->getBucket($testDestBucketName);

        $testSrcFileName = 'test_src_file.tmp';
        $testFileContent = 'Test file content';
        $srcBucket->saveFileContent($testSrcFileName, $testFileContent);
        $testDestFileName = 'test_dest_file.tmp';

        $srcFileRef = [
            $testSrcBucketName,
            $testSrcFileName
        ];
        $destFileRef = [
            $testDestBucketName,
            $testDestFileName
        ];
        $this->assertTrue($srcBucket->copyFileInternal($srcFileRef,$destFileRef), 'Unable to copy file internal between different buckets!');
        $this->assertTrue($destBucket->fileExists($testDestFileName), 'Unable to create destination file!');
    }

    /**
     * @depends testCopyFileInternalSameBucket
     */
    public function testMoveFileInternalSameBucket()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_move_file_internal_bucket';
        $bucket->setName($testBucketName);

        $testSrcFileName = 'test_src_file.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testSrcFileName,$testFileContent);

        $testDestFileName = 'test_dest_file.tmp';
        $this->assertTrue($bucket->moveFileInternal($testSrcFileName,$testDestFileName), 'Unable to move file internally in the same bucket!');
        $this->assertTrue($bucket->fileExists($testDestFileName), 'Unable to create destination file!');
        $this->assertEquals($testFileContent, $bucket->getFileContent($testDestFileName), 'Destination file has wrong content!');
        $this->assertFalse($bucket->fileExists($testSrcFileName), 'Unable to delete source file!');
    }

    /**
     * @depends testMoveFileInternalSameBucket
     */
    public function testMoveFileInternalDifferentBuckets()
    {
        $fileStorage = $this->createFileStorage();
        $testSrcBucketName = 'test_move_file_internal_src_bucket';
        $testDestBucketName = 'test_move_file_internal_dest_bucket';
        $buckets = [
            $testSrcBucketName,
            $testDestBucketName
        ];
        $fileStorage->setBuckets($buckets);

        $srcBucket = $fileStorage->getBucket($testSrcBucketName);
        $destBucket = $fileStorage->getBucket($testDestBucketName);

        $testSrcFileName = 'test_src_file.tmp';
        $testFileContent = 'Test file content';
        $srcBucket->saveFileContent($testSrcFileName, $testFileContent);
        $testDestFileName = 'test_dest_file.tmp';

        $srcFileRef = [
            $testSrcBucketName,
            $testSrcFileName
        ];
        $destFileRef = [
            $testDestBucketName,
            $testDestFileName
        ];
        $this->assertTrue($srcBucket->moveFileInternal($srcFileRef, $destFileRef), 'Unable to move file internal between different buckets!');
        $this->assertTrue($destBucket->fileExists($testDestFileName), 'Unable to create destination file!');
        $this->assertFalse($srcBucket->fileExists($testSrcFileName), 'Unable to delete the source file!');
    }

    /**
     * @depends testFileExists
     */
    public function testGetFileUrl()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_get_file_url_bucket']);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $returnedFileUrl = $bucket->getFileUrl($testFileName);
        $this->assertTrue(!empty($returnedFileUrl), 'File URL is empty!');
    }

    /**
     * @depends testGetFileUrl
     */
    public function testCreateFileUrl()
    {
        $bucket = $this->createFileStorageBucket(['name' => 'test_create_file_url_bucket']);
        $bucket->getStorage()->setBaseUrl(['/site/download']);

        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $returnedFileUrl = $bucket->getFileUrl($testFileName);
        $this->assertTrue(!empty($returnedFileUrl), 'File URL is empty!');
        $this->assertContains(urlencode('site/download'), $returnedFileUrl, 'File URL does not contain route!');
        $this->assertContains($bucket->getName(), $returnedFileUrl, 'File URL does not contain bucket name!');
        $this->assertContains($testFileName, $returnedFileUrl, 'File URL does not contain filename!');
    }

    /**
     * @depends testSaveFileContent
     */
    public function testSaveFileNameWithDirSeparator()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_save_file_name_with_dir_separator';
        $bucket->setName($testBucketName);

        $testFileNamePath = 'test_file_name_path';
        $testFileName = $testFileNamePath . DIRECTORY_SEPARATOR . 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $this->assertTrue($bucket->saveFileContent($testFileName, $testFileContent), 'Unable to save file with name, containing dir separator, content!');

        $this->assertTrue($bucket->fileExists($testFileName), 'Unable to create file with name, containing dir separator, in the bucket!');
    }

    /**
     * @depends testGetFileContent
     */
    public function testOpenFile()
    {
        $bucket = $this->createFileStorageBucket();
        $testBucketName = 'test_get_file_content_bucket';
        $bucket->setName($testBucketName);

        $testFileName = 'test_read_file_name.tmp';
        $testFileContent = 'Test read file content';
        $bucket->saveFileContent($testFileName, $testFileContent);

        $resource = $bucket->openFile($testFileName, 'r');
        $this->assertTrue(is_resource($resource));

        $this->assertEquals($testFileContent, stream_get_contents($resource));
        fclose($resource);

        $testFileName = 'test_write_file_name.tmp';
        $testFileContent = 'Test write file content';

        $resource = $bucket->openFile($testFileName, 'w');
        $this->assertTrue(is_resource($resource));

        fwrite($resource, $testFileContent);
        fclose($resource);

        $this->assertEquals($testFileContent, $bucket->getFileContent($testFileName));
    }
}