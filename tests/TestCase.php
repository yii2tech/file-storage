<?php

namespace yii2tech\tests\unit\filestorage;

use yii\helpers\ArrayHelper;
use Yii;
use yii\helpers\FileHelper;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array config params.
     */
    public static $params;

    /**
     * @var \yii\mongodb\Connection MongoDB connection instance.
     */
    protected $mongodb;
    /**
     * @var \yii2tech\filestorage\sftp\Connection SSH connection instance.
     */
    protected $ssh;


    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
        FileHelper::createDirectory($this->getTestTmpPath());
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        FileHelper::removeDirectory($this->getTestTmpPath());
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\web\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => $this->getVendorPath(),
            'components' => [
                'request' => [
                    'hostInfo' => 'http://domain.com',
                    'scriptUrl' => '/index.php'
                ],
            ],
        ], $config));
    }

    /**
     * @return string vendor path
     */
    protected function getVendorPath()
    {
        return dirname(dirname(__DIR__)) . '/vendor';
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

    /**
     * Returns a test configuration param from /data/config.php
     * @param string $name params name
     * @param mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(__DIR__ . '/data/config.php');
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    /**
     * @param boolean $reset whether to clean up the test database
     * @param boolean $open  whether to open test database
     * @return \yii\mongodb\Connection
     */
    public function getMongodb($reset = false, $open = true)
    {
        if (!$reset && $this->mongodb) {
            return $this->mongodb;
        }

        $config = self::getParam('mongodb');

        $db = new \yii\mongodb\Connection($config);

        $db->enableLogging = false;
        $db->enableProfiling = false;
        if ($open) {
            $db->open();
        }
        $this->mongodb = $db;

        return $db;
    }

    /**
     * @param boolean $reset whether to clean up the test connection
     * @return \yii2tech\filestorage\sftp\Connection SFTP connection instance.
     */
    public function getSsh($reset = false)
    {
        if (!$reset && $this->ssh) {
            return $this->ssh;
        }

        $config = self::getParam('ssh');

        $sftp = new \yii2tech\filestorage\sftp\Connection($config);

        $this->ssh = $sftp;

        return $sftp;
    }

    /**
     * Returns the path for the temporary files.
     * @return string temporary path
     */
    public function getTestTmpPath()
    {
        return Yii::getAlias('@yii2tech/tests/unit/filestorage/runtime/test_file_storage_tmp');
    }

    /**
     * Invokes object method, even if it is private or protected.
     * @param object $object object.
     * @param string $method method name.
     * @param array $args method arguments
     * @return mixed method result
     */
    protected function invoke($object, $method, array $args = [])
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);
        $result = $methodReflection->invokeArgs($object, $args);
        $methodReflection->setAccessible(false);
        return $result;
    }
}