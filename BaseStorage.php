<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\log\Logger;

/** 
 * BaseStorage is a base class for the file storages.
 * This class stores the file storage bucket instances and creates them based on
 * the configuration array.
 * Each particular file storage is supposed to use a particular class for its buckets.
 * Name of this class can be set through the {@link bucketClassName}.
 *
 * @property BucketInterface[] $buckets list of buckets.
 * @property string $bucketClassName name of the bucket class.
 * @property string|array $baseUrl web URL, which is basic for all buckets at [[BucketInterface::getFileUrl()]].
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BaseStorage extends Component implements StorageInterface
{
    /**
     * @var string name of the bucket class.
     */
    public $bucketClassName = 'yii2tech\filestorage\BaseBucket';

    /**
     * @var BucketInterface[] list of buckets.
     */
    private $_buckets = [];
    /**
     * @var string|array web URL, which is basic for all buckets.
     * You can setup this field as array, which will be treated as a route specification for [[\yii\helpers\Url::to()]].
     * @since 1.1.0
     */
    private $_baseUrl;


    /**
     * Logs a message.
     * @see Logger
     * @param string $message message to be logged.
     * @param integer $level the level of the message.
     */
    protected function log($message, $level = Logger::LEVEL_INFO)
    {
        if (!YII_DEBUG && $level === Logger::LEVEL_INFO) {
            return;
        }
        $category = get_class($this);
        Yii::getLogger()->log($message, $level, $category);
    }

    /**
     * Creates bucket instance based on the configuration array.
     * @param array $bucketConfig - configuration array for the bucket.
     * @return BucketInterface bucket instance.
     */
    protected function createBucketInstance(array $bucketConfig)
    {
        if (!array_key_exists('class', $bucketConfig)) {
            $bucketClassName = $this->bucketClassName;
            $bucketConfig['class'] = $bucketClassName;
        }
        $bucketConfig['storage'] = $this;
        return Yii::createObject($bucketConfig);
    }

    /**
     * Sets the list of available buckets.
     * @param array $buckets - set of bucket instances or bucket configurations.
     * @return boolean success.
     */
    public function setBuckets(array $buckets)
    {
        foreach ($buckets as $bucketKey => $bucketValue) {
            if (is_numeric($bucketKey) && is_string($bucketValue)) {
                $bucketName = $bucketValue;
                $bucketData = [];
            } else {
                $bucketName = $bucketKey;
                $bucketData = $bucketValue;
            }
            $this->addBucket($bucketName, $bucketData);
        }
        return true;
    }

    /**
     * Gets the list of available bucket instances.
     * @return BucketInterface[] set of bucket instances.
     */
    public function getBuckets()
    {
        $result = [];
        foreach ($this->_buckets as $bucketName => $bucketData) {
            $result[$bucketName] = $this->getBucket($bucketName);
        }
        return $result;
    }

    /**
     * Gets the bucket instance by name.
     * @param string $bucketName - name of the bucket.
     * @throws InvalidParamException if bucket does not exist.
     * @return BucketInterface set of bucket instances.
     */
    public function getBucket($bucketName)
    {
        if (!array_key_exists($bucketName, $this->_buckets)) {
            throw new InvalidParamException("Bucket named '{$bucketName}' does not exists in the file storage '" . get_class($this) . "'");
        }
        $bucketData = $this->_buckets[$bucketName];
        if (is_object($bucketData)) {
            $bucketInstance = $bucketData;
        } else {
            $bucketData['name'] = $bucketName;
            $bucketInstance = $this->createBucketInstance($bucketData);
            $this->_buckets[$bucketName] = $bucketInstance;
        }
        return $bucketInstance;
    }

    /**
     * Adds the bucket to the buckets list.
     * @param string $bucketName - name of the bucket.
     * @param mixed $bucketData - bucket instance or configuration array.
     * @throws InvalidParamException on invalid data.
     * @return boolean success.
     */
    public function addBucket($bucketName, $bucketData = [])
    {
        if (!is_string($bucketName)) {
            throw new InvalidParamException('Name of the bucket should be a string!');
        }
        if (is_scalar($bucketData)) {
            throw new InvalidParamException('Data of the bucket should be an bucket object or configuration array!');
        }
        if (is_object($bucketData)) {
            $bucketData->setName($bucketName);
        }
        $this->_buckets[$bucketName] = $bucketData;
        return true;
    }

    /**
     * Indicates if the bucket has been set up in the storage.
     * @param string $bucketName - name of the bucket.
     * @return boolean success.
     */
    public function hasBucket($bucketName)
    {
        return array_key_exists($bucketName, $this->_buckets);
    }

    /**
     * @inheritdoc
     */
    public function setBaseUrl($baseUrl)
    {
        if (is_string($baseUrl)) {
            $baseUrl = Yii::getAlias($baseUrl);
        }
        $this->_baseUrl = $baseUrl;
    }

    /**
     * @inheritdoc
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }
}