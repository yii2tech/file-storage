Yii 2 File Storage extension Change Log
=======================================

1.1.4, February 9, 2018
-----------------------

- Enh #10: Added `yii2tech\filestorage\local\Storage::$dirPermission` allowing setup of directory permissions different from file permissions (klimov-paul)


1.1.3, November 3, 2017
-----------------------

- Bug: Usage of deprecated `yii\base\Object` changed to `yii\base\BaseObject` allowing compatibility with PHP 7.2 (klimov-paul)


1.1.2, July 7, 2017
-------------------

- Enh #10: Added `yii2tech\amazon\Storage::$amazonS3Config` allowing setup of `Aws\S3\S3Client` instantiation options (klimov-paul)


1.1.1, June 22, 2017
--------------------

- Enh #8: Added `yii2tech\filestorage\DownloadAction::$inline` allowing to send inline file to the browser and providing `content-range` support (vuongminh)


1.1.0, November 17, 2016
------------------------

- Enh #4: Added `yii2tech\filestorage\BucketInterface::openFile()` allowing to open file as a PHP resource (klimov-paul)
- Enh #6: SFTP file storage implemented via `yii2tech\filestorage\sftp\Storage` (klimov-paul)
- Enh #7: Usage of URL routes for `BucketInterface::getFileUrl()` provided (klimov-paul)


1.0.1, April 25, 2016
---------------------

- Bug #2: Fixed `yii2tech\filestorage\amazon\Bucket` mismatches parent class interface (klimov-paul)


1.0.0, February 10, 2016
------------------------

- Initial release.
