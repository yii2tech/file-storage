Yii 2 File Storage extension Change Log
=======================================

1.1.0 under development
-----------------------

- Enh #4: Added `yii2tech\filestorage\BucketInterface::openFile()` allowing to open file as a PHP resource (klimov-paul)
- Enh #6: SFTP file storage implemented via `yii2tech\filestorage\sftp\Storage` (klimov-paul)
- Enh #7: Usage of URL routes for `BucketInterface::getFileUrl()` provided (klimov-paul)


1.0.1, April 25, 2016
---------------------

- Bug #2: Fixed `yii2tech\filestorage\amazon\Bucket` mismatches parent class interface (klimov-paul)


1.0.0, February 10, 2016
------------------------

- Initial release.
