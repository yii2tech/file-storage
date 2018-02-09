<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage;

/** 
 * StorageInterface is an interface for the all file storages.
 * File storage should be a hub for the [[BucketInterface]] instances.
 *
 * @see BucketInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface StorageInterface
{
    /**
     * Sets the list of available buckets.
     * @param array $buckets - set of bucket instances or bucket configurations.
     * @return bool success.
     */
    public function setBuckets(array $buckets);

    /**
     * Gets the list of available bucket instances.
     * @return BucketInterface[] set of bucket instances.
     */
    public function getBuckets();

    /**
     * Gets the bucket instance by name.
     * @param string $bucketName - name of the bucket.
     * @return BucketInterface bucket instance.
     */
    public function getBucket($bucketName);

    /**
     * Adds the bucket to the buckets list.
     * @param string $bucketName - name of the bucket.
     * @param mixed $bucketData - bucket instance or configuration array.
     * @return bool success.
     */
    public function addBucket($bucketName, $bucketData = []);

    /**
     * Indicates if the bucket has been set up in the storage.
     * @param string $bucketName - name of the bucket.
     * @return bool success.
     */
    public function hasBucket($bucketName);

    /**
     * Sets the base URL, which should be used by [[BucketInterface::getFileUrl()]].
     * Example values:
     *
     * ```php
     * 'http://files.domain.com',
     * '//files.domain.com',
     * '@web/files',
     * ['/file/download'],
     * ```
     *
     * @param string|array|null $baseUrl web URL, which is basic for all buckets.
     * If string given, the URL will be composed by pattern: `{baseUrl}/{bucketName}/{fileName}`.
     * If array given, it is considered as a route for URL creation via [[\yii\web\UrlManager]],
     * bucket name will be added as `bucket` param, and file name will be added as `filename`.
     * @since 1.1.0
     */
    public function setBaseUrl($baseUrl);

    /**
     * Returns the base URL, which should be used by [[BucketInterface::getFileUrl()]].
     * @return string|array|null web URL, which is basic for all buckets.
     * @since 1.1.0
     */
    public function getBaseUrl();
}