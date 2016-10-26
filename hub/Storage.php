<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\hub;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidParamException;
use yii2tech\filestorage\StorageInterface;

/** 
 * Storage introduces the complex file storage, which combines
 * several different file storages in the single facade.
 * While getting the particular bucket from this storage, you may never know
 * it is consist of several ones.
 * Note: to avoid any problems make sure all buckets from all storages have
 * unique name.
 *
 * Configuration example:
 *
 * ```php
 * 'fileStorage' => [
 *     'class' => 'yii2tech\filestorage\hub\Storage',
 *     'storages' => [
 *         [
 *             'class' => 'yii2tech\filestorage\local\Storage',
 *             ...
 *             'buckets' => [
 *                 'fileSystemBucket' => [...],
 *             ],
 *         ],
 *         [
 *             'class' => 'yii2tech\filestorage\ftp\Storage',
 *             ...
 *             'buckets' => [
 *                 'ftpBucket' => [...],
 *             ],
 *         ],
 *     ]
 * ]
 * ```
 *
 * Usage example:
 *
 * ```php
 * $fileSystemBucket = Yii::$app->fileStorage->getBucket('fileSystemBucket');
 * $ftpBucket = Yii::$app->fileStorage->getBucket('ftpBucket');
 * ```
 *
 * @property StorageInterface[] $storages list of internal storages.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Storage extends Component implements StorageInterface
{
    /**
     * @var StorageInterface[] list of internal storages.
     */
    private $_storages = [];


    /**
     * Creates file storage instance based on the configuration array.
     * @param array $storageConfig - configuration array for the file storage.
     * @return StorageInterface file storage instance.
     */
    protected function createStorageInstance(array $storageConfig)
    {
        return Yii::createObject($storageConfig);
    }

    /**
     * Sets the list of available file storages.
     * @param StorageInterface[]|array $storages - set of file storage instances or file storage configurations.
     * @return boolean success.
     */
    public function setStorages(array $storages)
    {
        $this->_storages = [];
        foreach ($storages as $storageKey => $storageValue) {
            if (is_numeric($storageKey) && is_string($storageValue)) {
                $storageName = $storageValue;
                $storageData = [];
            } else {
                $storageName = $storageKey;
                $storageData = $storageValue;
            }
            $this->addStorage($storageName, $storageData);
        }
        return true;
    }

    /**
     * Gets the list of available file storage instances.
     * @return StorageInterface[] set of file storage instances.
     */
    public function getStorages()
    {
        $result = [];
        foreach ($this->_storages as $storageName => $storageData) {
            $result[$storageName] = $this->getStorage($storageName);
        }
        return $result;
    }

    /**
     * Gets the file storage instance by name.
     * @param string $storageName - name of the storage.
     * @throws InvalidParamException if requested storage does not exist.
     * @return StorageInterface file storage instance.
     */
    public function getStorage($storageName)
    {
        if (!array_key_exists($storageName, $this->_storages)) {
            throw new InvalidParamException("Storage named '{$storageName}' does not exists in the file storage hub '" . get_class($this) . "'");
        }
        $storageData = $this->_storages[$storageName];
        if (is_object($storageData)) {
            $storageInstance = $storageData;
        } else {
            $storageInstance = $this->createStorageInstance($storageData);
            $this->_storages[$storageName] = $storageInstance;
        }
        return $storageInstance;
    }

    /**
     * Adds the storage to the storages list.
     * @param string $storageName - name of the storage.
     * @param mixed $storageData - storage instance or configuration array.
     * @throws InvalidParamException on invalid data.
     * @return boolean success.
     */
    public function addStorage($storageName, $storageData = [])
    {
        if (!is_string($storageName)) {
            throw new InvalidParamException('Name of the storage should be a string!');
        }
        if (is_scalar($storageData) || empty($storageData)) {
            throw new InvalidParamException('Data of the storage should be an file storage object or configuration array!');
        }
        $this->_storages[$storageName] = $storageData;
        return true;
    }

    /**
     * Indicates if the storage has been set up in the storage hub.
     * @param string $storageName - name of the storage.
     * @return boolean success.
     */
    public function hasStorage($storageName)
    {
        return array_key_exists($storageName, $this->_storages);
    }

    /**
     * Returns the default file storage, meaning the first one in the [[storages]] list.
     * @throws Exception on failure.
     * @return StorageInterface file storage instance.
     */
    protected function getDefaultStorage()
    {
        $storageList = $this->_storages;
        $storageNames = array_keys($storageList);
        $defaultStorageName = array_shift($storageNames);
        if (empty($defaultStorageName)) {
            throw new Exception('Unable to determine default storage in the hub!');
        }
        $storage = $this->getStorage($defaultStorageName);
        return $storage;
    }

    /**
     * Sets the list of available buckets.
     * @param array $buckets - set of bucket instances or bucket configurations.
     * @return boolean success.
     */
    public function setBuckets(array $buckets)
    {
        $storage = $this->getDefaultStorage();
        return $storage->setBuckets($buckets);
    }

    /**
     * Gets the list of available bucket instances.
     * @return array set of bucket instances.
     */
    public function getBuckets()
    {
        $buckets = [];
        foreach ($this->getStorages() as $storage) {
            $buckets = array_merge($storage->getBuckets(), $buckets);
        }
        return $buckets;
    }

    /**
     * Gets the bucket instance by name.
     * @param string $bucketName - name of the bucket.
     * @throws InvalidParamException if requested bucket does not exist.
     * @return array set of bucket instances.
     */
    public function getBucket($bucketName)
    {
        $storagesList = $this->_storages;
        foreach ($storagesList as $storageName => $storageData) {
            $storage = $this->getStorage($storageName);
            if ($storage->hasBucket($bucketName)) {
                return $storage->getBucket($bucketName);
            }
        }
        throw new InvalidParamException("Bucket named '{$bucketName}' does not exists in any file storage of the hub '" . get_class($this) . "'");
    }

    /**
     * Adds the bucket to the buckets list.
     * @param string $bucketName - name of the bucket.
     * @param mixed $bucketData - bucket instance or configuration array.
     * @return boolean success.
     */
    public function addBucket($bucketName, $bucketData = [])
    {
        $storage = $this->getDefaultStorage();
        return $storage->addBucket($bucketName, $bucketData);
    }

    /**
     * Indicates if the bucket has been set up in the storage.
     * @param string $bucketName - name of the bucket.
     * @return boolean success.
     */
    public function hasBucket($bucketName)
    {
        $storagesList = $this->_storages;
        foreach ($storagesList as $storageName => $storageData) {
            $storage = $this->getStorage($storageName);
            if ($storage->hasBucket($bucketName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function setBaseUrl($baseUrl)
    {
        foreach ($this->getStorages() as $storage) {
            $storage->setBaseUrl($baseUrl);
        }
    }

    /**
     * @inheritdoc
     */
    public function getBaseUrl()
    {
        foreach ($this->getStorages() as $storage) {
            return $storage->getBaseUrl();
        }
        return null;
    }
}