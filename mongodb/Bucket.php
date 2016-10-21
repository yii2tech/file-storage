<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\mongodb;

use yii2tech\filestorage\BaseBucket;

/**
 * Bucket
 *
 * @property string|array $collectionPrefix related MongoDB GridFS collection prefix.
 * @property \yii\mongodb\file\Collection $collection related MongoDB GridFS collection.
 * @method Storage getStorage()
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.1.0
 */
class Bucket extends BaseBucket
{
    /**
     * @var string|array related MongoDB GridFS collection prefix.
     * @see \yii\mongodb\Connection::getFileCollection()
     */
    private $_collectionPrefix;


    /**
     * @return array|string related MongoDB GridFS collection prefix.
     */
    public function getCollectionPrefix()
    {
        if ($this->_collectionPrefix === null) {
            $this->_collectionPrefix = $this->defaultCollectionPrefix();
        }
        return $this->_collectionPrefix;
    }

    /**
     * @param array|string $collectionPrefix related MongoDB GridFS collection prefix.
     */
    public function setCollectionPrefix($collectionPrefix)
    {
        $this->_collectionPrefix = $collectionPrefix;
    }

    /**
     * @return array|string default collection name.
     */
    protected function defaultCollectionPrefix()
    {
        $name = $this->getName();
        return preg_replace('/([^A-Z0-9_])/is', '_', $name);
    }

    /**
     * @return \yii\mongodb\file\Collection related MongoDB GridFS collection.
     */
    public function getCollection()
    {
        return $this->getStorage()->db->getFileCollection($this->getCollectionPrefix());
    }

    /**
     * @inheritdoc
     */
    public function create()
    {
        $this->getCollection();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function destroy()
    {
        return $this->getCollection()->drop();
    }

    /**
     * @inheritdoc
     */
    public function exists()
    {
        $collectionPrefix = $this->getCollectionPrefix();
        if (is_array($collectionPrefix)) {
            list($database, $prefix) = $collectionPrefix;
        } else {
            $database = null;
            $prefix = $collectionPrefix;
        }
        return !empty($this->getStorage()->db->getDatabase($database)->listCollections(['name' => $prefix . '.files']));
    }

    /**
     * Saves content as new file.
     * @param string $fileName - new file name.
     * @param string $content - new file content.
     * @return boolean success.
     */
    public function saveFileContent($fileName, $content)
    {
        // TODO: Implement saveFileContent() method.
    }

    /**
     * Returns content of an existing file.
     * @param string $fileName - new file name.
     * @return string $content - file content.
     */
    public function getFileContent($fileName)
    {
        // TODO: Implement getFileContent() method.
    }

    /**
     * Deletes an existing file.
     * @param string $fileName - new file name.
     * @return boolean success.
     */
    public function deleteFile($fileName)
    {
        // TODO: Implement deleteFile() method.
    }

    /**
     * Checks if the file exists in the bucket.
     * @param string $fileName - searching file name.
     * @return boolean file exists.
     */
    public function fileExists($fileName)
    {
        // TODO: Implement fileExists() method.
    }

    /**
     * Copies file from the OS file system into the bucket.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return boolean success.
     */
    public function copyFileIn($srcFileName, $fileName)
    {
        // TODO: Implement copyFileIn() method.
    }

    /**
     * Copies file from the bucket into the OS file system.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return boolean success.
     */
    public function copyFileOut($fileName, $destFileName)
    {
        // TODO: Implement copyFileOut() method.
    }

    /**
     * Copies file inside this bucket or between this bucket and another
     * bucket of this file storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $srcFile - this bucket existing file name or array reference to another bucket file name.
     * @param mixed $destFile - this bucket existing file name or array reference to another bucket file name.
     * @return boolean success.
     */
    public function copyFileInternal($srcFile, $destFile)
    {
        // TODO: Implement copyFileInternal() method.
    }

    /**
     * Copies file from the OS file system into the bucket and
     * deletes the source file.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return boolean success.
     */
    public function moveFileIn($srcFileName, $fileName)
    {
        // TODO: Implement moveFileIn() method.
    }

    /**
     * Copies file from the bucket into the OS file system and
     * deletes the source bucket file.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return boolean success.
     */
    public function moveFileOut($fileName, $destFileName)
    {
        // TODO: Implement moveFileOut() method.
    }

    /**
     * Moves file inside this bucket or between this bucket and another
     * bucket of this file storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $srcFile - this bucket existing file name or array reference to another bucket file name.
     * @param mixed $destFile - this bucket existing file name or array reference to another bucket file name.
     * @return boolean success.
     */
    public function moveFileInternal($srcFile, $destFile)
    {
        // TODO: Implement moveFileInternal() method.
    }

    /**
     * Gets web URL of the file.
     * @param string $fileName - self file name.
     * @return string file web URL.
     */
    public function getFileUrl($fileName)
    {
        // TODO: Implement getFileUrl() method.
    }

    /**
     * @inheritdoc
     */
    public function openFile($fileName, $mode, $context = null)
    {
        // TODO: Implement openFile() method.
    }
}