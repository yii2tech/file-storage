<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\amazon;

use yii\base\InvalidConfigException;
use yii\log\Logger;
use yii2tech\filestorage\BucketSubDirTemplate;

/**
 * Bucket introduces the bucket of file storage based on
 * Amazon Simple Storage Service (S3).
 *
 * @see Storage
 * @see https://github.com/aws/aws-sdk-php
 * @see http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-s3.html
 *
 * @property string $urlName storage DNS URL name.
 * @method Storage getStorage()
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Bucket extends BucketSubDirTemplate
{
    /**
     * @var string Amazon region name of the bucket.
     * Only required if using Version 2 of the AWS PHP SDK.
     * You can setup this value as the real region name
     * or a short alias of the real region name
     * according the following map:
     *
     * ```php
     * 'us_e1' => 'us-east-1'
     * 'us_w1' => 'us-west-1'
     * 'us_w2' => 'us-west-2'
     * 'eu_w1' => 'eu-west-1'
     * 'apac_se1' => 'ap-southeast-1'
     * 'apac_se2' => 'ap-southeast-2'
     * 'apac_ne1' => 'ap-northeast-1'
     * 'sa_e1' => 'sa-east-1'
     * ```
     *
     * @see AmazonS3
     */
    public $region = null;
    /**
     * @var mixed bucket ACL policy.
     * You can setup this value as the real ACL policy name
     * or a short alias of the real ACL policy name
     * according the following map:
     *
     * ```php
     * 'private' => 'private'
     * 'public' => 'public-read'
     * 'open' => 'public-read-write'
     * 'auth_read' => 'authenticated-read'
     * 'owner_read' => 'bucket-owner-read'
     * 'owner_full_control' => 'bucket-owner-full-control'
     * ```
     *
     * @see AmazonS3
     */
    public $acl = 'private';
    /**
     * @var string encryption type
     * Requires Version 3 of the AWS PHP SDK.
     */
    public $serverSideEncryption = null;

    /**
     * @var string Storage DNS URL name.
     * This name will be used as actual bucket name in Amazon S3.
     * If this field is left blank its value will be
     * generated using [[name]].
     * @see \Aws\S3\S3Client::isValidBucketName()
     */
    private $_urlName;
    /**
     * @var string actual value of [[region]].
     * This field is for the internal usage only.
     */
    private $_actualRegion;
    /**
     * @var string actual value of [[acl]].
     * This field is for the internal usage only.
     */
    private $_actualAcl;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->getStorage()->isAmazonSDKVersion2()) {
            if ($this->region == null) {
                $this->region = 'eu_w1';
            }
        }
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
     * Returns the actual Amazon region value from the [[region]].
     * @throws InvalidConfigException on invalid region.
     * @return string actual Amazon region.
     */
    protected function getActualRegion()
    {
        if (empty($this->_actualRegion)) {
            $region = $this->region;
            if (empty($region)) {
                throw new InvalidConfigException('"' . get_class($this) . '::region" can not be empty.');
            }
            $this->_actualRegion = $this->fetchActualRegion($region);
        }
        return $this->_actualRegion;
    }

    /**
     * Returns the actual Amazon region value from the [[region]].
     * @param string $region raw region value.
     * @return string actual Amazon region.
     */
    protected function fetchActualRegion($region)
    {
        switch ($region) {
            // USA :
            case 'us_e1': {
                return 'us-east-1';
            }
            case 'us_w1': {
                return 'us-west-1';
            }
            case 'us_w2': {
                return 'us-west-2';
            }
            // Europe :
            case 'eu_w1': {
                return 'eu-west-1';
            }
            // AP :
            case 'apac_se1': {
                return 'ap-southeast-1';
            }
            case 'apac_se2': {
                return 'ap-southeast-2';
            }
            case 'apac_ne1': {
                return 'ap-northeast-1';
            }
            // South America :
            case 'sa_e1': {
                return 'sa-east-1';
            }
        }
        return $region;
    }

    /**
     * Returns the actual Amazon bucket ACL value from the [[acl]].
     * @throws InvalidConfigException on invalid ACL.
     * @return string actual Amazon bucket ACL.
     */
    protected function getActualAcl()
    {
        if (empty($this->_actualAcl)) {
            $acl = $this->acl;
            if (empty($acl)) {
                throw new InvalidConfigException('"' . get_class($this) . '::acl" can not be empty.');
            }
            $this->_actualAcl = $this->fetchActualAcl($acl);
        }
        return $this->_actualAcl;
    }

    /**
     * Returns the actual Amazon bucket ACL value from the [[acl]]
     * @param string $acl raw ACL value.
     * @return string actual Amazon bucket ACL.
     */
    protected function fetchActualAcl($acl)
    {
        switch ($acl) {
            case 'public': {
                return 'public-read';
            }
            case 'open': {
                return 'public-read-write';
            }
            case 'auth_read': {
                return 'authenticated-read';
            }
            case 'owner_read': {
                return 'bucket-owner-read';
            }
            case 'owner_full_control': {
                return 'bucket-owner-full-control';
            }
        }
        return $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $args = [
            'Bucket' => $this->getUrlName(),
            'ACL' => $this->getActualAcl(),
        ];
        if ($this->getStorage()->isAmazonSDKVersion2()) {
            $args['LocationConstraint'] = $this->getActualRegion();
        }
        $amazonS3 = $this->getStorage()->getAmazonS3();
        $amazonS3->createBucket($args);
        $this->log('bucket has been created with URL name "' . $this->getUrlName() . '"');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        $amazonS3 = $this->getStorage()->getAmazonS3();
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
        $amazonS3 = $this->getStorage()->getAmazonS3();
        $result = $amazonS3->doesBucketExist($this->getUrlName());
        $this->_internalCache['exists'] = $result;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function saveFileContent($fileName, $content)
    {
        if (!$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getStorage()->getAmazonS3();
        $args = [
            'Bucket' => $this->getUrlName(),
            'Key' => $fileName,
            'Body' => $content,
            'ACL' => $this->getActualAcl(),
        ];
        if ($this->serverSideEncryption) {
            $args['ServerSideEncryption'] = $this->serverSideEncryption;
        }
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
        if (!$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getStorage()->getAmazonS3();
        $args = [
            'Bucket' => $this->getUrlName(),
            'Key' => $fileName,
        ];
        if ($this->serverSideEncryption) {
            $args['ServerSideEncryption'] = $this->serverSideEncryption;
        }
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
        if (!$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getStorage()->getAmazonS3();
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
     * {@inheritdoc}
     */
    public function fileExists($fileName)
    {
        if (!$this->exists()) {
            $this->create();
        }
        $fileName = $this->getFullFileName($fileName);
        $amazonS3 = $this->getStorage()->getAmazonS3();
        return $amazonS3->doesObjectExist($this->getUrlName(), $fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileIn($srcFileName, $fileName)
    {
        $fileContent = file_get_contents($srcFileName);
        return $this->saveFileContent($fileName, $fileContent);
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
        if (!$this->exists()) {
            $this->create();
        }

        $storage = $this->getStorage();
        $amazonS3 = $storage->getAmazonS3();

        $srcFileRef = $this->getFileAmazonComplexReference($srcFile);
        $destFileRef = $this->getFileAmazonComplexReference($destFile);

        $srcBucket = $storage->getBucket($srcFileRef['bucket']);
        if (!$srcBucket->exists()) {
            $srcBucket->create();
            $srcFileRef['filename'] = $srcBucket->getFullFileName($srcFileRef['filename']);
        }
        $srcFileRef['bucket'] = $srcBucket->getUrlName();

        $destBucket = $storage->getBucket($destFileRef['bucket']);
        if (!$destBucket->exists()) {
            $destBucket->create();
            $destFileRef['filename'] = $destBucket->getFullFileName($destFileRef['filename']);
        }
        $destFileRef['bucket'] = $destBucket->getUrlName();

        $args = [
            'Bucket' => $destFileRef['bucket'],
            'Key' => $destFileRef['filename'],
            'CopySource' => urlencode($srcFileRef['bucket'] . '/' . $srcFileRef['filename']),
        ];
        if ($this->serverSideEncryption) {
            $args['ServerSideEncryption'] = $this->serverSideEncryption;
        }

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
    protected function composeFileUrl($baseUrl, $fileName)
    {
        if ($baseUrl === null) {
            if (!$this->exists()) {
                $this->create();
            }
            $fileName = $this->getFullFileName($fileName);
            $amazonS3 = $this->getStorage()->getAmazonS3();
            return $amazonS3->getObjectUrl($this->getUrlName(), $fileName);
        }
        return parent::composeFileUrl($baseUrl, $fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function openFile($fileName, $mode, $context = null)
    {
        $this->getStorage()->registerStreamWrapper();

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
     * @return bool success.
     */
    public function saveFileContentBatch(array $fileContents)
    {
        if (!$this->exists()) {
            $this->create();
        }
        $amazonS3 = $this->getStorage()->getAmazonS3();

        $commands = [];
        foreach ($fileContents as $fileName => $fileContent) {
            $args = [
                'Bucket' => $this->getUrlName(),
                'ACL' => $this->getActualAcl(),
                'Key' => $this->getFullFileName($fileName),
                'Body' => $fileContent,
            ];
            if ($this->serverSideEncryption) {
                $args['ServerSideEncryption'] = $this->serverSideEncryption;
            }
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
        if (!$this->exists()) {
            $this->create();
        }
        $amazonS3 = $this->getStorage()->getAmazonS3();

        $commands = [];
        foreach ($filesMap as $srcFileName => $bucketFileName) {
            $args = [
                'Bucket' => $this->getUrlName(),
                'ACL' => $this->getActualAcl(),
                'Key' => $this->getFullFileName($bucketFileName),
                'Body' => file_get_contents($srcFileName),
            ];
            if ($this->serverSideEncryption) {
                $args['ServerSideEncryption'] = $this->serverSideEncryption;
            }
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