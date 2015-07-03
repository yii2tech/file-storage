<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\local;

use Yii;
use yii2tech\filestorage\BaseStorage;

/** 
 * Storage introduces the file storage based simply on the OS local file system.
 *
 * Configuration example:
 *
 * ```php
 * 'fileStorage' => [
 *     'class' => 'yii2tech\filestorage\local\Storage',
 *     'basePath' => '@webroot/files',
 *     'baseUrl' => '@web/files',
 *     'filePermission' => 0777,
 *     'buckets' => [
 *         'tempFiles' => [
 *             'baseSubPath' => 'temp',
 *             'fileSubDirTemplate' => '{^name}/{^^name}',
 *         ],
 *         'imageFiles' => [
 *             'baseSubPath' => 'image',
 *             'fileSubDirTemplate' => '{ext}/{^name}/{^^name}',
 *         ],
 *     ]
 * ]
 * ```
 *
 * @see Bucket
 *
 * @property string $basePath file system path, which is basic for all buckets.
 * @property string $baseUrl web URL, which is basic for all buckets.
 * @method Bucket getBucket($bucketName)
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Storage extends BaseStorage
{
    /**
     * @inheritdoc
     */
    public $bucketClassName = 'yii2tech\filestorage\filesystem\Bucket';
    /**
     * @var integer the chmod permission for directories and files,
     * created in the process. Defaults to 0755 (owner rwx, group rx and others rx).
     */
    public $filePermission = 0755;
    /**
     * @var string file system path, which is basic for all buckets.
     */
    private $_basePath = '';
    /**
     * @var string web URL, which is basic for all buckets.
     */
    private $_baseUrl = '';


    /**
     * @param string $basePath file system path, which is basic for all buckets.
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = Yii::getAlias($basePath);
    }

    /**
     * @return string file system path, which is basic for all buckets.
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * @param string $baseUrl web URL, which is basic for all buckets.
     */
    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = Yii::getAlias($baseUrl);
    }

    /**
     * @return string web URL, which is basic for all buckets.
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }
}