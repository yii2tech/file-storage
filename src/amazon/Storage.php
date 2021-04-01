<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\amazon;

use Aws\S3\S3Client;
use yii\base\InvalidConfigException;
use yii2tech\filestorage\BaseStorage;

/**
 * Storage introduces the file storage based on Amazon Simple Storage Service (S3).
 *
 * In order to use this storage you need to install [aws/aws-sdk-php](https://github.com/aws/aws-sdk-php) version 3:
 *
 * ```
 * composer require --prefer-dist aws/aws-sdk-php:^3.0
 * ```
 *
 * Configuration example:
 *
 * ```php
 * 'fileStorage' => [
 *     'class' => 'yii2tech\filestorage\amazon\Storage',
 *     'awsKey' => 'AWSKEY',
 *     'awsSecretKey' => 'AWSSECRETKEY',
 *     'buckets' => [
 *         'tempFiles' => [
 *             'region' => 'eu-west-1',
 *             'acl' => 'private',
 *         ],
 *         'imageFiles' => [
 *             'region' => 'eu-west-1',
 *             'acl' => 'public',
 *         ],
 *     ]
 * ]
 * ```
 *
 * @see Bucket
 * @see https://github.com/aws/aws-sdk-php
 * @see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/welcome.html
 *
 * @method Bucket getBucket($bucketName)
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Storage extends BaseStorage
{
    /**
     * {@inheritdoc}
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
     * @var array Depricated (use $defaultAmazonS3Config). Additional default configuration options for all S3 clients.
     * Please refer to [[S3Client::__construct()]] for available options list.
     * @see S3Client::__construct()
     * @since 1.1.2
     * @deprecated Since v1.2.0, use $defaultAmazonS3Config instead.
     */
    public $amazonS3Config = [];

    /**
     * @var array additional default configuration options for all S3 clients.
     * Please refer to [[S3Client::__construct()]] for available options list.
     * @see S3Client::__construct()
     * @since 1.2.0
     */
    public $defaultAmazonS3Config = [];

    /**
     * @var string The version of the web service to use
     * Please see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html#cfg-version
     * @see S3Client::__construct()
     * @since 1.2.0
     */
    public $amazonS3ClientVersion = '2006-03-01';

    /**
     * @var string Default Amazon region name of the buckets.
     * @see https://docs.aws.amazon.com/general/latest/gr/rande.html#regional-endpoints
     * @since 1.2.0
     */
    public $defaultRegion = 'eu-west-1';
    
    /**
     * @var bool whether `s3` stream wrapper has been already registered.
     */
    private $streamWrapperRegistered = false;

    /**
     * Initializes the instance of the Amazon S3 service gateway.
     * @return S3Client Amazon S3 client instance.
     */
    public function createAmazonS3($region = null, $amazonS3Config = [])
    {
        if (empty($region)) {
            $region = $this->defaultRegion;
        }

        $credentials = [];
        if (!empty($this->awsKey) && !empty($this->awsSecretKey)) {
            $credentials = [
                'key' => $this->awsKey,
                'secret' => $this->awsSecretKey,
            ];
        }

        $clientConfig = array_merge(
            [
                'version' => $this->amazonS3ClientVersion,
                'region' => $region,
            ],
            $credentials,
            $this->amazonS3Config, # For backwards compatibility only
            $this->defaultAmazonS3Config,
            $amazonS3Config
        );
        return new S3Client($clientConfig);
    }

    /**
     * Registers Amazon S3 stream wrapper for the `s3` protocol.
     * @param Bucket $bucket
     * @param bool $force whether to enforce registration even wrapper has been already registered.
     * @since 1.1.0
     */
    public function registerStreamWrapper($bucket, $force = false)
    {
        if ($force || !$this->streamWrapperRegistered) {
            $bucket->getAmazonS3()->registerStreamWrapper();
            $this->streamWrapperRegistered = true;
        }
    }
}