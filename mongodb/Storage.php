<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\mongodb;

use yii\di\Instance;
use yii\mongodb\Connection;
use yii2tech\filestorage\BaseStorage;

/**
 * Storage introduces the file storage based on the [MongoDB](http://www.mongodb.org/) [GridFS](http://docs.mongodb.org/manual/core/gridfs/).
 *
 * In order to use this storage you need to install [yiisoft/yii2-mongodb](https://github.com/yiisoft/yii2-mongodb):
 *
 * ```
 * composer require --prefer-dist yiisoft/yii2-mongodb:~2.1.0
 * ```
 *
 * @see http://docs.mongodb.org/manual/core/gridfs/
 *
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
    public $bucketClassName = 'yii2tech\filestorage\mongodb\Bucket';
    /**
     * @var Connection|array|string the MongoDB connection object or the application component ID of the MongoDB connection.
     * After the Storage object is created, if you want to change this property, you should only assign it
     * with a MongoDB connection object.
     */
    public $db = 'mongodb';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }
}