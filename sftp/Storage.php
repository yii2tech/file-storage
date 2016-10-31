<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\sftp;

use yii\di\Instance;
use yii2tech\filestorage\BaseStorage;

/**
 * Storage introduces the file storage based on the SSH2 SFTP
 *
 * @see Connection
 *
 * @property resource $sftp related SFTP subsystem session.
 * @method Bucket getBucket($bucketName)
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.1.0
 */
class Storage extends BaseStorage
{
    /**
     * @inheritdoc
     */
    public $bucketClassName = 'yii2tech\filestorage\sftp\Bucket';
    /**
     * @var Connection|array|string the SSH connection object or the application component ID of the SSH connection.
     * After the Storage object is created, if you want to change this property, you should only assign it
     * with a SSH connection object.
     */
    public $ssh;
    /**
     * @var string remote server file system path, which is basic for all buckets.
     * If not set, it will be composed by pattern `/home/{username}/files`, where `{username}` will be picked up
     * from [[Connection::username]].
     */
    public $basePath;
    /**
     * @var integer the chmod permission for directories and files,
     * created in the process. Defaults to 0755 (owner rwx, group rx and others rx).
     */
    public $filePermission = 0755;

    /**
     * @var resource related SFTP subsystem session.
     */
    private $_sftp;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->ssh = Instance::ensure($this->ssh, Connection::className());
        if ($this->basePath === null) {
            $this->basePath = '/home/' . $this->ssh->username . '/files';
        }
    }

    /**
     * @return resource related SFTP subsystem session.
     */
    public function getSftp()
    {
        if ($this->_sftp === null) {
            $this->_sftp = ssh2_sftp($this->ssh->getSession());
        }
        return $this->_sftp;
    }
}