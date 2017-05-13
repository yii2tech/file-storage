<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage;

use Yii;
use yii\base\Action;
use yii\di\Instance;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * DownloadAction provides the web access for the files stored in the file storage.
 *
 * This action can be used in case particular storage does not provide native support for files web access,
 * like [[\yii2tech\filestorage\mongodb\Storage]], or in case you want to restrict web access for the stored files,
 * for example: allow access only for the logged in user.
 *
 * Configuration example:
 *
 * ```php
 * class FileController extends \yii\web\Controller
 * {
 *     public function actions()
 *     {
 *         return [
 *             'download' => [
 *                 'class' => 'yii2tech\filestorage\DownloadAction',
 *             ],
 *         ];
 *     }
 * }
 * ```
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.1.0
 */
class DownloadAction extends Action
{
    /**
     * @var StorageInterface|array|string file storage object or the application component ID of the file storage.
     */
    public $fileStorage = 'fileStorage';
    /**
     * @var array list of bucket names that this action should allow to use. If this property is not set,
     * then the action allows to all buckets available in [[fileStorage]], unless they are listed in [[exceptBuckets]].
     * If a bucket name appears in both [[onlyBuckets]] and [[exceptBuckets]], this action will NOT allow it to be used.
     */
    public $onlyBuckets;
    /**
     * @var array list of the bucket names, that this action should not allow to use.
     * @see onlyBuckets
     */
    public $exceptBuckets = [];
    /**
     * @var bool whether to check the requested file existence before attempt to get its content.
     * You may disable this option in order to achieve better performance, however in this case
     * action flow may produce PHP error with some storages.
     */
    public $checkFileExistence = true;
    /**
     * @var bool|callable whether the browser should open the file within the browser window.
     * Defaults to false, meaning a download dialog will pop up.
     * This value can be specified as a PHP callback of following signature:
     *
     * ```php
     * function (\yii2tech\filestorage\BucketInterface $bucket, string $filename) {
     *     //return bool whether file should be send inline or not
     * }
     * ```
     *
     * @since 1.1.1
     */
    public $inline = false;


    /**
     * Runs the action.
     * @param string $bucket name of the file source bucket
     * @param string $filename name of the file to be downloaded.
     * @return Response response.
     * @throws NotFoundHttpException if bucket or file does not exist.
     */
    public function run($bucket, $filename)
    {
        if (!$this->isBucketAllowed($bucket)) {
            throw new NotFoundHttpException("Bucket '{$bucket}' does not exist.");
        }

        $this->fileStorage = Instance::ensure($this->fileStorage, 'yii2tech\filestorage\StorageInterface');

        if (!$this->fileStorage->hasBucket($bucket)) {
            throw new NotFoundHttpException("Bucket '{$bucket}' does not exist.");
        }

        $bucket = $this->fileStorage->getBucket($bucket);

        if ($this->checkFileExistence && !$bucket->fileExists($filename)) {
            throw new NotFoundHttpException("File '{$filename}' does not exist at bucket '{$bucket->getName()}' does not exist.");
        }

        $mimeType = FileHelper::getMimeTypeByExtension($filename);

        $inline = is_callable($this->inline) ? call_user_func($this->inline, $bucket, $filename) : $this->inline;

        $handle = $bucket->openFile($filename, 'r');

        return Yii::$app->getResponse()->sendStreamAsFile($handle, basename($filename), [
            'inline' => $inline,
            'mimeType' => $mimeType
        ]);
    }

    /**
     * Returns a value indicating whether the download from the specified bucket is allowed or not.
     * @param string $bucketName the name of the bucket.
     * @return bool whether the download from the bucket is allowed or not.
     */
    protected function isBucketAllowed($bucketName)
    {
        return !in_array($bucketName, $this->exceptBuckets, true) && (empty($this->onlyBuckets) || in_array($bucketName, $this->onlyBuckets, true));
    }
}