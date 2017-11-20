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
 *     'dirPermission' => 0775,
 *     'filePermission' => 0755,
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
    public $bucketClassName = 'yii2tech\filestorage\local\Bucket';
    /**
     * @var int the chmod permission for directories and files,
     * created in the process. Defaults to 0755 (owner rwx, group rx and others rx).
     */
    public $filePermission = 0755;
    /**
     * @var int the chmod permission for the directories created in the process.
     * If not set - value of [[filePermission]] will be used.
     * @since 1.1.4
     */
    public $dirPermission;
    /**
     * @var string file system path, which is basic for all buckets.
     */
    private $_basePath = '';


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if ($this->dirPermission === null) {
            $this->dirPermission = $this->filePermission;
        }
    }

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
}