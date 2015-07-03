<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\amazon;

use yii\base\InvalidConfigException;
use yii2tech\filestorage\BaseStorage;

/**
 * Storage introduces the file storage based on
 * Amazon Simple Storage Service (S3).
 *
 * @see Bucket
 * @see https://github.com/aws/aws-sdk-php
 * @see http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-s3.html
 *
 * @property \Aws\S3\S3Client $amazonS3 instance of the Amazon S3 client.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Storage extends BaseStorage
{
    /**
     * @inheritdoc
     */
    public $bucketClassName = 'yii2tech\filestorage\amazon\Bucket';
    /**
     * @var string AWS (Amazon Web Service) key.
     * If constant 'AWS_KEY' has been defined, this field can be left blank.
     */
    public $awsKey = '';
    /**
     * @var string AWS (Amazon Web Service) secret key.
     * If constant 'AWS_SECRET_KEY' has been defined, this field can be left blank.
     */
    public $awsSecretKey = '';
    /**
     * @var \Aws\S3\S3Client instance of the Amazon S3 client.
     */
    private $_amazonS3;


    public function setAmazonS3($amazonS3)
    {
        if (!is_object($amazonS3)) {
            throw new InvalidConfigException('"' . get_class($this) . '::amazonS3" should be an object!');
        }
        $this->_amazonS3 = $amazonS3;
    }

    /**
     * @return \Aws\S3\S3Client
     */
    public function getAmazonS3()
    {
        if (!is_object($this->_amazonS3)) {
            $this->_amazonS3 = $this->createAmazonS3();
        }
        return $this->_amazonS3;
    }

    /**
     * Initializes the instance of the Amazon S3 service gateway.
     * @return \Aws\S3\S3Client Amazon S3 client instance.
     */
    protected function createAmazonS3()
    {
        $amazonS3Options = [
            'key' => $this->awsKey,
            'secret' => $this->awsSecretKey,
        ];
        return \Aws\S3\S3Client::factory($amazonS3Options);
    }
}