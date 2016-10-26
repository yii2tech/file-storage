<?php

namespace yii2tech\tests\unit\filestorage;

use Yii;
use yii\web\Controller;
use yii2tech\filestorage\DownloadAction;
use yii2tech\filestorage\local\Storage;

class DownloadActionTest extends TestCase
{
    /**
     * @param array $config action configuration.
     * @return DownloadAction action instance.
     */
    protected function createAction($config = [])
    {
        $controller = new Controller('test', Yii::$app);
        return new DownloadAction('download', $controller, $config);
    }

    /**
     * @return Storage file storage instance.
     */
    protected function createFileStorage()
    {
        return new Storage([
            'basePath' => $this->getTestTmpPath(),
            'buckets' => [
                'temp' => [
                    'baseSubPath' => 'temp'
                ],
                'item' => [
                    'baseSubPath' => 'item'
                ],
            ]
        ]);
    }

    // Tests :

    public function testSuccess()
    {
        $fileStorage = $this->createFileStorage();
        $action = $this->createAction(['fileStorage' => $fileStorage]);
        $bucket = $fileStorage->getBucket('temp');

        $fileName = 'success.txt';
        $fileContent = 'Success content';
        $bucket->saveFileContent($fileName, $fileContent);

        $response = $action->run($bucket->getName(), $fileName);
        $this->assertEquals($fileContent, $response->content);
        $this->assertEquals('text/plain', $response->getHeaders()->get('content-type'));
    }

    public function testInvalidBucket()
    {
        $fileStorage = $this->createFileStorage();
        $action = $this->createAction(['fileStorage' => $fileStorage]);

        $this->setExpectedException('yii\web\NotFoundHttpException');

        $response = $action->run('unexisting', 'some.txt');
    }

    public function testInvalidFileName()
    {
        $fileStorage = $this->createFileStorage();
        $action = $this->createAction(['fileStorage' => $fileStorage]);
        $bucket = $fileStorage->getBucket('temp');

        $this->setExpectedException('yii\web\NotFoundHttpException');

        $response = $action->run($bucket->getName(), 'some.txt');
    }

    /**
     * Data provider for [[testIsBucketAllowed()]]
     * @return array test data
     */
    public function dataProviderIsBucketAllowed()
    {
        return [
            [
                [
                    'onlyBuckets' => ['temp']
                ],
                'temp',
                true
            ],
            [
                [
                    'onlyBuckets' => ['temp']
                ],
                'item',
                false
            ],
            [
                [
                    'exceptBuckets' => ['temp']
                ],
                'temp',
                false
            ],
            [
                [
                    'exceptBuckets' => ['temp']
                ],
                'item',
                true
            ],
        ];
    }

    /**
     * @dataProvider dataProviderIsBucketAllowed
     *
     * @param array $actionConfig
     * @param string $bucketName
     * @param boolean $expectedResult
     */
    public function testIsBucketAllowed($actionConfig, $bucketName, $expectedResult)
    {
        $actionConfig['fileStorage'] = $this->createFileStorage();
        $action = $this->createAction($actionConfig);

        $this->assertEquals($expectedResult, $this->invoke($action, 'isBucketAllowed', [$bucketName]));
    }
}