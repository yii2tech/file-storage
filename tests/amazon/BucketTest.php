<?php

namespace yii2tech\tests\unit\filestorage\amazon;

use yii\helpers\FileHelper;
use Yii;
use yii2tech\filestorage\amazon\Bucket;
use yii2tech\filestorage\amazon\Storage;
use yii2tech\tests\unit\filestorage\TestCase;

/**
 * Test case for [[Bucket]]
 * @see Bucket
 *
 * @group amazon
 */
class BucketTest extends TestCase
{
    public function tearDown()
    {
        $fileStorage = $this->createFileStorage();
        $amazonS3 = $fileStorage->getAmazonS3();
        /* @var $response \Guzzle\Service\Resource\Model */
        $response = $amazonS3->listBuckets();
        $buckets = $response->get('Buckets');
        foreach($buckets as $bucket) {
            $bucketName = $bucket['Name'];
            if (strpos($bucketName, 'test')===0) {
                $response = $amazonS3->listObjects(array(
                    'Bucket' => $bucketName
                ));
                $bucketObjects = $response->get('Contents');
                if (is_array($bucketObjects)) {
                    foreach ($bucketObjects as $bucketObject) {
                        $amazonS3->deleteObject(array(
                            'Bucket' => $bucketName,
                            'Key' => $bucketObject['Key'],
                        ));
                    }
                }
                $amazonS3->deleteBucket(array(
                    'Bucket' => $bucketName,
                ));
            }
        }

        parent::tearDown();
    }

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
            'awsSecretKey' => $this->getAwsSecretKey(),
        ]);
    }

    /**
     * Creates test file storage bucket.
     * @param string $name bucket name.
     * @return Bucket file storage bucket instance.
     */
    protected function createFileStorageBucket($name = '')
    {
        return new Bucket([
            'storage' => $this->createFileStorage(),
            'region' => 'eu_w1',
            'acl' => 'private',
            'name' => $name,
        ]);
    }

    // Tests:

    public function testSetGet()
    {
        $bucket = $this->createFileStorageBucket();
        
        $testUrlName = 'test-url-name';
        $bucket->setUrlName($testUrlName);
        $this->assertEquals($bucket->getUrlName(), $testUrlName, 'Unable to set URL name correctly!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetDefaultUrlName()
    {
        $bucket = $this->createFileStorageBucket();
        $testName = 'test_name-test+name';
        $bucket->setName($testName);

        $searches = [
            '_',
            '+',
        ];
        $expectedUrlName = str_replace($searches, '-', $testName);
        $defaultUrlName =  $bucket->getUrlName();
        $this->assertEquals($expectedUrlName, $defaultUrlName, 'Wrong default URL name!');
    }

    /**
     * @depends testSetGet
     */
    public function testCreateBucket()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-create');
        
        $this->assertTrue($bucket->create(), 'Unable to create bucket!');
        $this->assertTrue(true, 'Unable to create bucket!');
    }

    /**
     * @depends testCreateBucket
     */
    public function testBucketDestroy()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-destroy');
        $bucket->create();
        
        $this->assertTrue($bucket->destroy(), 'Unable to destroy bucket!');
    }

    /**
     * @depends testBucketDestroy
     */
    public function testBucketExists()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-exists');
        
        $this->assertFalse($bucket->exists(), 'Not yet created bucket exists!');
        
        $bucket->create();
        $this->assertTrue($bucket->exists(), 'Created bucket does not exists!');
        
        $bucket->destroy();
        $this->assertFalse($bucket->exists(), 'Destroyed bucket exists!');
    }

    /**
     * @depends testBucketExists
     */
    public function testSaveFileContent()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-save-file-content');
        
        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $this->assertTrue($bucket->saveFileContent($testFileName, $testFileContent), 'Unable to save file content!');
    }

    /**
     * @depends testSaveFileContent
     */
    public function testGetFileContent()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-get-file-content');
        
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
        $bucket = $this->createFileStorageBucket('test-bucket-delete-file');
        
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
        $bucket = $this->createFileStorageBucket('test-bucket-exists-file');
        
        $testFileName = 'test_file_name.tmp';
        
        //$this->assertFalse( $bucket->fileExists($testFileName), 'Not saved yet file exists!' );
        
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
        $bucket = $this->createFileStorageBucket('test-bucket-copy-file-in');
        
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
        $bucket = $this->createFileStorageBucket('test-bucket-copy-file-out');
        
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
        $bucket = $this->createFileStorageBucket('test-bucket-move-file-in');
        
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
        $bucket = $this->createFileStorageBucket('test-bucket-move-file-out');
        
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
        $fileStorage = $this->createFileStorage();
        $testBucketName = 'test-bucket-copy-file-internal';
        $buckets = [
            $testBucketName => [
                'region' => 'eu_w1'
            ],
        ];
        $fileStorage->setBuckets($buckets);
        $bucket = $fileStorage->getBucket($testBucketName);
        
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
        $testSrcBucketName = 'test-bucket-copy-file-internal-src';
        $testDestBucketName = 'test-bucket-copy-file-internal-dest';
        $buckets = [
            $testSrcBucketName => [
                'region' => 'eu_w1'
            ],
            $testDestBucketName => [
                'region' => 'eu_w1'
            ],
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
        $fileStorage = $this->createFileStorage();
        $testBucketName = 'test-bucket-move-file-internal';
        $buckets = [
            $testBucketName => [
                'region' => 'eu_w1'
            ],
        ];
        $fileStorage->setBuckets($buckets);
        $bucket = $fileStorage->getBucket($testBucketName);
        
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
        $testSrcBucketName = 'test-bucket-move-file-internal-src';
        $testDestBucketName = 'test-bucket-move-file-internal-dest';
        $buckets = [
            $testSrcBucketName => [
                'region' => 'eu_w1'
            ],
            $testDestBucketName => [
                'region' => 'eu_w1'
            ],
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
        $this->assertTrue($srcBucket->moveFileInternal($srcFileRef,$destFileRef), 'Unable to move file internal between different buckets!');
        $this->assertTrue($destBucket->fileExists($testDestFileName), 'Unable to create destination file!');
        $this->assertFalse($srcBucket->fileExists($testSrcFileName), 'Unable to delete the source file!');
    }

    /**
     * @depends testFileExists
     */
    public function testGetFileUrl()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-get-file-url');
        
        $testFileName = 'test_file_name.tmp';
        $testFileContent = 'Test file content';
        $bucket->saveFileContent($testFileName, $testFileContent);
        
        $returnedFileUrl = $bucket->getFileUrl($testFileName);
        $this->assertTrue(!empty($returnedFileUrl), 'File URL is empty!');
        
        /*$expectedFileUrl = $bucket->getStorage()->getBaseUrl().'/'.$bucket->getBaseSubPath().'/'.$testFileName;
        $this->assertEquals( $expectedFileUrl, $returnedFileUrl, 'Wrong file URL returned!' );*/
    }

    /**
     * @depends testGetFileContent
     */
    public function testOpenFile()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-open-file');

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

    /**
     * @depends testSaveFileContent
     */
    public function testSaveFileContentBatch()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-save-file-content-batch');

        $files = [
            'test_file_name_1.tmp' => 'Test file content 1',
            'test_file_name_2.tmp' => 'Test file content 2',
        ];
        $this->assertTrue($bucket->saveFileContentBatch($files), 'Unable to save files content as batch!');
    }

    /**
     * @depends testCopyFileIn
     */
    public function testCopyFileInBatch()
    {
        $bucket = $this->createFileStorageBucket('test-bucket-copy-file-in-batch');

        $files = [
            'test_file_name_1.tmp' => 'Test file content 1',
            'test_file_name_2.tmp' => 'Test file content 2',
        ];
        $fileMap = [];
        $tmpPath = $this->getTestTmpPath();
        foreach ($files as $name => $content) {
            $srcFileName = $tmpPath . DIRECTORY_SEPARATOR . $name;
            file_put_contents($srcFileName, $content);
            $fileMap[$srcFileName] = 'bucket_' . $name;
        }
        $this->assertTrue($bucket->copyFileInBatch($fileMap), 'Unable to copy files into the bucket as batch!');
    }
}
