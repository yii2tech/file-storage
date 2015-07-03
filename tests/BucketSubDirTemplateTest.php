<?php

namespace yii2tech\tests\unit\filestorage;

use yii2tech\filestorage\BucketSubDirTemplate;

/**
 * Test case for the extension [[BucketSubDirTemplate]].
 * @see BucketSubDirTemplate
 */
class BucketSubDirTemplateTest extends TestCase
{
    /**
     * Get file storage bucket mock object.
     * @return BucketSubDirTemplate file storage bucket instance.
     */
    protected function createFileStorageBucket()
    {
        $methodsList = [
            'create',
            'destroy',
            'exists',
            'saveFileContent',
            'getFileContent',
            'deleteFile',
            'fileExists',
            'copyFileIn',
            'copyFileOut',
            'copyFileInternal',
            'moveFileIn',
            'moveFileOut',
            'moveFileInternal',
            'getFileUrl',
        ];
        $bucket = $this->getMock('yii2tech\filestorage\BucketSubDirTemplate', $methodsList);
        return $bucket;
    }

    // Tests :

    public function testResolveFileSubDirTemplate()
    {
        $bucket = $this->createFileStorageBucket();

        $testFileSubDirTemplate = '{ext}/{^name}/{^^name}';
        $bucket->fileSubDirTemplate = $testFileSubDirTemplate;

        $testFileSelfName = 'test_file_self_name';
        $testFileExtension = 'tmp';
        $testFileName = $testFileSelfName . '.' . $testFileExtension;

        $returnedFullFileName = $bucket->getFileNameWithSubDir($testFileName);

        $expectedFullFileName = $testFileExtension . '/';
        $expectedFullFileName .= substr($testFileName, 0, 1) . '/';
        $expectedFullFileName .= substr($testFileName, 1, 1) . '/';
        $expectedFullFileName .= $testFileName;

        $this->assertEquals($expectedFullFileName, $returnedFullFileName, 'Unable to resolve file sub dir correctly!');
    }
}
