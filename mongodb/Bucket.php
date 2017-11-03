<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\mongodb;

use yii\base\InvalidParamException;
use yii2tech\filestorage\BaseBucket;

/**
 * Bucket introduces the file storage bucket based simply on the [MongoDB](http://www.mongodb.org/) [GridFS](http://docs.mongodb.org/manual/core/gridfs/).
 *
 * Configuration example:
 *
 * ```php
 * 'fileStorage' => [
 *     'class' => 'yii2tech\filestorage\mongodb\Storage',
 *     'baseUrl' => ['/file/download'], // should lead to `\yii2tech\filestorage\DownloadAction`
 *     'buckets' => [
 *         'tempFiles' => [
 *             'collectionPrefix' => 'temp',
 *         ],
 *         'imageFiles' => [
 *             'collectionPrefix' => 'image',
 *         ],
 *     ]
 * ]
 * ```
 *
 * @see Storage
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
     * Finds the MongoDB document with file information.
     * @param string $fileName file name.
     * @return array|null MongoDB document.
     */
    protected function findDocument($fileName)
    {
        return $this->getCollection()->getFileCollection()->findOne(['filename' => $fileName]);
    }

    /**
     * Creates MongoDB GridFS file download.
     * @param string $fileName file name.
     * @return \yii\mongodb\file\Download MongoDB GridFS file download.
     */
    protected function createFileDownload($fileName)
    {
        $document = $this->findDocument($fileName);
        if (empty($document)) {
            throw new InvalidParamException("File '{$fileName}' does not exist.");
        }
        return $this->getCollection()->createDownload($document);
    }

    /**
     * Creates MongoDB GridFS file upload.
     * @param string $fileName file name.
     * @return \yii\mongodb\file\Upload MongoDB GridFS file upload.
     */
    protected function createFileUpload($fileName)
    {
        return $this->getCollection()->createUpload(['filename' => $fileName]);
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $collection = $this->getCollection();

        $database = $collection->database;

        $database->createCollection($collection->getFileCollection()->name);
        $database->createCollection($collection->getChunkCollection()->name);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        return $this->getCollection()->drop();
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        $collection = $this->getCollection();
        $database = $collection->database;

        $collectionNames = $database->listCollections(['name' => $collection->getFileCollection()->name]);

        return !empty($collectionNames);
    }

    /**
     * {@inheritdoc}
     */
    public function saveFileContent($fileName, $content)
    {
        $this->createFileUpload($fileName)->addContent($content)->complete();
        $this->log("file '{$fileName}' has been saved");
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileContent($fileName)
    {
        $download = $this->createFileDownload($fileName);
        $this->log("content of file '{$fileName}' has been returned");
        return $download->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile($fileName)
    {
        $document = $this->findDocument($fileName);
        if (empty($document)) {
            $this->log("unable to delete file '{$fileName}': file does not exist");
            return true;
        }
        $deleteCount = $this->getCollection()->remove(['_id' => $document['_id']], ['limit' => 1]);
        $this->log("file '{$fileName}' has been deleted");
        return $deleteCount > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists($fileName)
    {
        $document = $this->findDocument($fileName);
        return !empty($document);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileIn($srcFileName, $fileName)
    {
        $this->createFileUpload($fileName)->addFile($srcFileName)->complete();
        $this->log("file '{$srcFileName}' has been copied to '{$fileName}'");
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileOut($fileName, $destFileName)
    {
        $this->createFileDownload($fileName)->toFile($destFileName);
        $this->log("file '{$fileName}' has been copied to '{$destFileName}'");
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileInternal($srcFile, $destFile)
    {
        if (is_array($srcFile)) {
            list($bucketName, $srcFileName) = $srcFile;
            $srcBucket = $this->getStorage()->getBucket($bucketName);
        } else {
            $srcBucket = $this;
            $srcFileName = $srcFile;
        }

        if (is_array($destFile)) {
            list($bucketName, $destFileName) = $destFile;
            $destBucket = $this->getStorage()->getBucket($bucketName);
        } else {
            $destBucket = $this;
            $destFileName = $destFile;
        }

        $srcDocument = $srcBucket->findDocument($srcFileName);

        $destDocument = $srcDocument;
        unset($destDocument['_id']);
        $destDocument['filename'] = $destFileName;

        $destCollection = $destBucket->getCollection();
        $destDocument['_id'] = $destCollection->getFileCollection()->insert($destDocument);

        $destChunkCollection = $destCollection->getChunkCollection();
        foreach ($srcBucket->getCollection()->getChunkCollection()->find(['files_id' => $srcDocument['_id']]) as $srcChunk) {
            $destChunk = $srcChunk;
            unset($destChunk['_id']);
            $destChunk['files_id'] = $destDocument['_id'];
            $destChunkCollection->insert($destChunk);
        }

        $this->log("file '{$srcFileName}' has been copied to '{$destFileName}'");

        return true;
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
        return ($this->copyFileOut($fileName, $destFileName) && $this->deleteFile($fileName));
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileInternal($srcFile, $destFile)
    {
        if (!$this->copyFileInternal($srcFile, $destFile)) {
            return false;
        }

        if (is_array($srcFile)) {
            list($bucketName, $srcFileName) = $srcFile;
            $srcBucket = $this->getStorage()->getBucket($bucketName);
        } else {
            $srcBucket = $this;
            $srcFileName = $srcFile;
        }
        $srcBucket->deleteFile($srcFileName);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function openFile($fileName, $mode, $context = null)
    {
        $storage = $this->getStorage();
        $storage->registerStreamWrapper();

        $collection = $this->getCollection();

        $path = $storage->db->fileStreamProtocol . '://' . $collection->database->name . '.' . $collection->prefix . '?filename=' . $fileName;

        if ($context === null) {
            $context = stream_context_create([
                $storage->db->fileStreamProtocol => [
                    'db' => $storage->db
                ]
            ]);
        }

        return fopen($path, $mode, null, $context);
    }
}