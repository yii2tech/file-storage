<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage;

use Yii;
use yii\base\BaseObject;
use yii\helpers\Url;
use yii\log\Logger;

/** 
 * BaseBucket is a base class for the file storage buckets.
 *
 * @property string $name bucket name.
 * @property StorageInterface $storage file storage, which owns the bucket.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BaseBucket extends BaseObject implements BucketInterface
{
    /**
     * @var string bucket name.
     */
    private $_name = '';
    /**
     * @var StorageInterface file storage, which owns the bucket.
     */
    private $_storage;


    /**
     * Logs a message.
     * @see Logger
     * @param string $message message to be logged.
     * @param int $level the level of the message.
     */
    protected function log($message, $level = Logger::LEVEL_INFO)
    {
        if (!YII_DEBUG && $level === Logger::LEVEL_INFO) {
            return;
        }
        $category = get_class($this) . '(' . $this->getName() . ')';
        Yii::getLogger()->log($message, $level, $category);
    }

    /**
     * Sets bucket name.
     * @param string $name - bucket name.
     * @return bool success.
     */
    public function setName($name)
    {
        $this->_name = $name;
        return true;
    }

    /**
     * Gets current bucket name.
     * @return string $name - bucket name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets bucket file storage.
     * @param StorageInterface $storage - file storage.
     * @return bool success.
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->_storage = $storage;
        return true;
    }

    /**
     * Gets bucket file storage.
     * @return StorageInterface - bucket file storage.
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileUrl($fileName)
    {
        $baseUrl = $this->getStorage()->getBaseUrl();
        if (is_array($baseUrl)) {
            $url = $baseUrl;
            $url['bucket'] = $this->getName();
            $url['filename'] = $fileName;
            return Url::to($url);
        }

        return $this->composeFileUrl($baseUrl, $fileName);
    }

    /**
     * Composes file URL from the base URL and filename.
     * This method is invoked at [[getFileUrl()]] in case base URL does not specify a URL route.
     * @param string|null $baseUrl storage base URL.
     * @param string $fileName self file name.
     * @return string file web URL.
     * @since 1.1.0
     */
    protected function composeFileUrl($baseUrl, $fileName)
    {
        return $baseUrl . '/' . urlencode($this->getName()) . '/' . $fileName;
    }
}