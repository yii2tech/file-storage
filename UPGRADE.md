Upgrading Instructions for File Storage Extension for Yii 2
===========================================================

!!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is,
if you want to upgrade from version A to version C and there is
version B between A and C, you need to following the instructions
for both A and B.

Upgrade from 1.0.1
----------------------

* Methods `setBaseUrl()` and `getBaseUrl()` have been added to `\yii2tech\filestorage\StorageInterface`.
  You should implement these new methods in case you create your own storage.

* Method `openFile()` has been added to `\yii2tech\filestorage\BucketInterface`.
  You should implement this new method in case you create your own storage bucket.
