<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\amazon;

use Aws\S3\BatchDelete;
use Aws\S3\S3Client;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\log\Logger;
use yii2tech\filestorage\BucketSubDirTemplate;

/**
 * Bucket introduces the bucket of file storage based on
 * Amazon Simple Storage Service (S3).
 *
 * @see Storage
 * @see https://github.com/aws/aws-sdk-php
 * @see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/welcome.html
 *
 * @property S3Client $amazonS3 instance of the Amazon S3 client.
 * @property string $urlName storage DNS URL name.
 * @method Storage getStorage()
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Bucket extends BucketSubDirTemplate
{
    /**
     * @var array additional configuration options for the S3 client of this bucket.
     * Please refer to [[S3Client::__construct()]] for available options list.
     * @see S3Client::__construct()
     * @since 1.2.0
     */
    public $amazonS3Config = [];

    /**
     * @var string Amazon region name of the bucket.
     *
     * @see https://docs.aws.amazon.com/general/latest/gr/rande.html#regional-endpoints
     */
    public $region = null;
    
    /**
     * @var mixed bucket ACL policy.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#canned-acl
     */
    public $acl = 'private';

    public $autoCreateBucket = true;

    /**
     * @var S3Client instance of the Amazon S3 client.
     */
    private $_amazonS3;

    /**
     * @var string Storage DNS URL name.
     * This name will be used as actual bucket name in Amazon S3.
     * If this field is left blank its value will be
     * generated using [[name]].
     * @see \Aws\S3\S3Client::isValidBucketName()
     */
    private $_urlName;

    /**
     * @param S3Client $amazonS3 Amazon S3 client.
     * @throws InvalidConfigException on invalid argument.
     */
    public function setAmazonS3($amazonS3)
    {
        if (!is_object($amazonS3)) {
            throw new InvalidConfigException('"' . get_class($this) . '::amazonS3" should be an object!');
        }
        $this->_amazonS3 = $amazonS3;
    }

    /**
     * @return S3Client Amazon S3 client instance.
     */
    public function getAmazonS3()
    {
        if (!is_object($this->_amazonS3)) {
            $this->_amazonS3 = $this->getStorage()->createAmazonS3($this->region, $this->amazonS3Config);
        }
        return $this->_amazonS3;
    }

    /**
     * @param string $urlName storage DNS URL name.
     */
    public function setUrlName($urlName)
    {
        $this->_urlName = $urlName;
    }

    /**
     * @return string storage DNS URL name.
     */
    public function getUrlName()
    {
        if ($this->_urlName === null) {
            $this->_urlName = $this->defaultUrlName();
        }
        return $this->_urlName;
    }

    /**
     * Initializes URL name using [[name]].
     * @return string URL name
     */
    protected function defaultUrlName()
    {
        $urlName = $this->getName();
        return preg_replace('/([^A-Z|^0-9|^-])/is', '-', $urlName);
    }

    /**
     * Returns the full internal file name, including
     * path resolved from [[BucketSubDirTemplate::$fileSubDirTemplate]].
     * @param string $fileName - name of the file.
     * @return string full name of the file.
     */
    public function getFullFileName($fileName)
    {
        return $this->getFileNameWithSubDir($fileName);
    }

    /**
     * Creates Amazon S3 file complex reference, which includes bucket name and file self name.
     * Such reference can be passed to [[\Aws\S3\S3Client::copyObject()]].
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $file - this bucket existing file name or array reference to another bucket file name.
     * @return array Amazon S3 file complex reference.
     */
    protected function getFileAmazonComplexReference($file)
    {
        if (is_array($file)) {
            list($bucketName, $fileName) = $file;
        } else {
            $bucketName = $this->getName();
            $fileName = $file;
        }
        $result = [
            'bucket' => $bucketName,
            'filename' => $fileName
        ];
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $amazonS3 = $this->getAmazonS3();
        $amazonS3->createBucket([
            'Bucket' => $this->getUrlName(),
            'LocationConstraint' => $this->region,
            'ACL' => $this->acl,
        ]);
        $this->log('bucket has been created with URL name "' . $this->getUrlName() . '"');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        $amazonS3 = $this->getAmazonS3();
        $amazonS3->deleteBucket([
            'Bucket' => $this->getUrlName(),
        ]);
        $this->clearInternalCache();
        $this->log('bucket has been destroyed with URL name "' . $this->getUrlName() . '"');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        if (isset($this->_internalCache['exists'])) {
            return true;
        }
        $amazonS3 = $this->getAmazonS3();
        $result = $amazonS3->doesBucketExist($this->getUrlName());
        $this->_internalCache['exists'] = $result;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function saveFileContent($fileName, $content, $metaData = [])
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getAmazonS3();
        $args = ArrayHelper::merge(
            [
                'Bucket' => $this->getUrlName(),
                'Key' => $fileName,
                'Body' => $content,
                'ACL' => $this->acl,
            ],
            $metaData
        );

        try {
            $amazonS3->putObject($args);
            $this->log("file '{$fileName}' has been saved");
            $result = true;
        } catch (\Exception $exception) {
            $this->log("unable to save file '{$fileName}':" . $exception->getMessage() . "!", Logger::LEVEL_ERROR);
            $result = false;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileContent($fileName)
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getAmazonS3();
        $args = [
            'Bucket' => $this->getUrlName(),
            'Key' => $fileName,
        ];
        $response = $amazonS3->getObject($args);
        $fileContent = $response['Body'];
        $this->log("content of file '{$fileName}' has been returned");
        return $fileContent;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile($fileName)
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getAmazonS3();
        $args = [
            'Bucket' => $this->getUrlName(),
            'Key' => $fileName,
        ];
        try {
            $amazonS3->deleteObject($args);
            $this->log("file '{$fileName}' has been deleted");
            $result = true;
        } catch (\Exception $exception) {
            $this->log("unable to delete file '{$fileName}':" . $exception->getMessage() . "!", Logger::LEVEL_ERROR);
            $result = false;
        }
        return $result;
    }

    /**
     * @param string $prefix The prefix of file keys to delete
     * @throws \Aws\S3\Exception\DeleteMultipleObjectsException
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.S3.BatchDelete.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjects
     */
    public function batchDeleteFiles($prefix)
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }

        $amazonS3 = $this->getAmazonS3();
        $listObjectsParams = [
            'Bucket' => $this->getUrlName(),
            'Prefix' => $prefix
        ];
        $delete = BatchDelete::fromListObjects($amazonS3, $listObjectsParams); // Asynchronously delete
        $promise = $delete->promise(); // Force synchronous completion
        $delete->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists($fileName)
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getAmazonS3();
        return $amazonS3->doesObjectExist($this->getUrlName(), $fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileIn($srcFileName, $fileName, $metaData = [])
    {
        $fileContent = file_get_contents($srcFileName);
        return $this->saveFileContent($fileName, $fileContent, $metaData);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileOut($fileName, $destFileName)
    {
        $fileContent = $this->getFileContent($fileName);
        $bytesWritten = file_put_contents($destFileName, $fileContent);
        return ($bytesWritten > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileInternal($srcFile, $destFile)
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }

        $storage = $this->getStorage();
        $amazonS3 = $storage->getAmazonS3();

        $srcFileRef = $this->getFileAmazonComplexReference($srcFile);
        $destFileRef = $this->getFileAmazonComplexReference($destFile);

        $srcBucket = $storage->getBucket($srcFileRef['bucket']);

        if ($this->autoCreateBucket && !$srcBucket->exists()) {
            $srcBucket->create();
            $srcFileRef['filename'] = $srcBucket->getFullFileName($srcFileRef['filename']);
            $srcFileRef['bucket'] = $srcBucket->getUrlName();
        }

        $destBucket = $storage->getBucket($destFileRef['bucket']);
        if ($this->autoCreateBucket && !$destBucket->exists()) {
            $destBucket->create();
            $destFileRef['filename'] = $destBucket->getFullFileName($destFileRef['filename']);
            $destFileRef['bucket'] = $destBucket->getUrlName();
        }

        $args = [
            'Bucket' => $destFileRef['bucket'],
            'Key' => $destFileRef['filename'],
            'CopySource' => urlencode($srcFileRef['bucket'] . '/' . $srcFileRef['filename']),
        ];

        try {
            $amazonS3->copyObject($args);
            $this->log("file '{$srcFileRef['bucket']}/{$srcFileRef['filename']}' has been copied to '{$destFileRef['bucket']}/{$destFileRef['filename']}'");
            $result = true;
        } catch (\Exception $exception) {
            $this->log("Unable to copy file from '{$srcFileRef['bucket']}/{$srcFileRef['filename']}' to '{$destFileRef['bucket']}/{$destFileRef['filename']}':" . $exception->getMessage() . "!", Logger::LEVEL_ERROR);
            $result = false;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileIn($srcFileName, $fileName)
    {
        return ($this->copyFileIn($srcFileName, $fileName) && unlink($srcFileName));
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileOut($fileName, $destFileName)
    {
        $result = $this->copyFileOut($fileName, $destFileName);
        if ($result) {
            $result = $result && $this->deleteFile($fileName);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileInternal($srcFile, $destFile)
    {
        $result = $this->copyFileInternal($srcFile, $destFile);
        if ($result) {
            $srcFileRef = $this->getFileAmazonComplexReference($srcFile);
            $bucketName = $srcFileRef['bucket'];
            $fileName = $srcFileRef['filename'];
            $bucket = $this->getStorage()->getBucket($bucketName);
            $result = $result && $bucket->deleteFile($fileName);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function composeFileUrl($baseUrl, $fileName, $includeBucketName = true)
    {
        if ($baseUrl === null) {
            if ($this->autoCreateBucket && !$this->exists()) {
                $this->create();
            }
            $fileName = $this->getFullFileName($fileName);
            $amazonS3 = $this->getAmazonS3();
            return $amazonS3->getObjectUrl($this->getUrlName(), $fileName);
        }
        return parent::composeFileUrl($baseUrl, $fileName, $includeBucketName);
    }

    /**
     * {@inheritdoc}
     */
    public function openFile($fileName, $mode, $context = null)
    {
        $this->getStorage()->registerStreamWrapper($this, true);

        $streamPath = 's3://' . $this->getUrlName() . '/' . $fileName;

        if ($mode === 'r' && func_num_args() < 3) {
            $context = stream_context_create([
                's3' => [
                    'seekable' => true
                ]
            ]);
        }

        if ($context === null) {
            // avoid PHP warning: fopen() expects parameter 4 to be resource, null given
            return fopen($streamPath, $mode);
        }
        return fopen($streamPath, $mode, null, $context);
    }

    // Batch files upload :

    /**
     * Saves given files content in parallel.
     * @param array $fileContents files content in format: fileName => fileContent
     * @param array $metaData Meta data applied to all files
     * @return bool success.
     */
    public function saveFileContentBatch(array $fileContents, $metaData = [])
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }
        $amazonS3 = $this->getAmazonS3();

        $commands = [];
        foreach ($fileContents as $fileName => $fileContent) {
            $args = ArrayHelper::merge(
                [
                    'Bucket' => $this->getUrlName(),
                    'ACL' => $this->acl,
                    'Key' => $this->getFullFileName($fileName),
                    'Body' => $fileContent,
                ],
                $metaData
            );
            $commands[] = $amazonS3->getCommand('PutObject', $args);
        }
        try {
            $amazonS3->execute($commands);
            $this->log("files batch has been saved");
            $result = true;
        } catch (\Exception $exception) {
            $this->log("unable to save files batch:" . $exception->getMessage() . "!", Logger::LEVEL_ERROR);
            $result = false;
        }
        return $result;
    }

    /**
     * Copies given files into the bucket in parallel.
     * @param array $filesMap files map in format: `srcFileName => bucketFileName`
     * @return bool success.
     */
    public function copyFileInBatch(array $filesMap)
    {
        if ($this->autoCreateBucket && !$this->exists()) {
            $this->create();
        }
        $amazonS3 = $this->getAmazonS3();

        $commands = [];
        foreach ($filesMap as $srcFileName => $bucketFileName) {
            $args = [
                'Bucket' => $this->getUrlName(),
                'ACL' => $this->acl,
                'Key' => $this->getFullFileName($bucketFileName),
                'Body' => file_get_contents($srcFileName),
            ];
            $commands[] = $amazonS3->getCommand('PutObject', $args);
        }
        try {
            $amazonS3->execute($commands);
            $this->log("files batch has been saved");
            $result = true;
        } catch (\Exception $exception) {
            $this->log("unable to save files batch:" . $exception->getMessage() . "!", Logger::LEVEL_ERROR);
            $result = false;
        }
        return $result;
    }
}