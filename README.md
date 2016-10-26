File Storage Extension for Yii 2
================================

This extension provides file storage abstraction layer for Yii2.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/file-storage/v/stable.png)](https://packagist.org/packages/yii2tech/file-storage)
[![Total Downloads](https://poser.pugx.org/yii2tech/file-storage/downloads.png)](https://packagist.org/packages/yii2tech/file-storage)
[![Build Status](https://travis-ci.org/yii2tech/file-storage.svg?branch=master)](https://travis-ci.org/yii2tech/file-storage)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/file-storage
```

or add

```json
"yii2tech/file-storage": "*"
```

to the require section of your composer.json.

If you wish to use Amazon S3 storage, you should also install [aws/aws-sdk-php](https://github.com/aws/aws-sdk-php) version 2.
Either run

```
php composer.phar require --prefer-dist aws/aws-sdk-php:~2.0
```

or add

```json
"aws/aws-sdk-php": "~2.0"
```

to the require section of your composer.json.

If you wish to use MongoDB storage, you should also install [yiisoft/yii2-mongodb](https://github.com/yiisoft/yii2-mongodb) version 2.1.
Either run

```
php composer.phar require --prefer-dist yiisoft/yii2-mongodb:~2.1.0
```

or add

```json
"yiisoft/yii2-mongodb": "2.1.0"
```

to the require section of your composer.json.


Usage
-----

This extension provides file storage abstraction layer for Yii2.
This abstraction introduces 2 main terms: 'storage' and 'bucket'.
'Storage' - is a unit, which is able to store files using some particular approach.
'Bucket' - is a logical part of the storage, which has own specific attributes and serves some logical mean.
Each time you need to read/write a file you should do it via particular bucket, which is always belongs to the
file storage.

Example application configuration:

```php
return [
    'components' => [
        'fileStorage' => [
            'class' => 'yii2tech\filestorage\local\Storage',
            'basePath' => '@webroot/files',
            'baseUrl' => '@web/files',
            'filePermission' => 0777,
            'buckets' => [
                'tempFiles' => [
                    'baseSubPath' => 'temp',
                    'fileSubDirTemplate' => '{^name}/{^^name}',
                ],
                'imageFiles' => [
                    'baseSubPath' => 'image',
                    'fileSubDirTemplate' => '{ext}/{^name}/{^^name}',
                ],
            ]
        ],
        // ...
    ],
    // ...
];
```

Example usage:

```php
$bucket = Yii::$app->fileStorage->getBucket('tempFiles');

$bucket->saveFileContent('foo.txt', 'Foo content'); // create file with content
$bucket->deleteFile('foo.txt'); // deletes file from bucket
$bucket->copyFileIn('/path/to/source/file.txt', 'file.txt'); // copy file into the bucket
$bucket->copyFileOut('file.txt', '/path/to/destination/file.txt'); // copy file from the bucket
var_dump($bucket->fileExists('file.txt')); // outputs `true`
echo $bucket->getFileUrl('file.txt'); // outputs: 'http://domain.com/files/f/i/file.txt'
```

Following file storages are available with this extension:
 - [[\yii2tech\filestorage\local\Storage]] - stores files on the OS local file system.
 - [[\yii2tech\filestorage\amazon\Storage]] - stores files using Amazon simple storage service (S3).
 - [[\yii2tech\filestorage\mongodb\Storage]] - stores files using MongoDB GridFS.
 - [[\yii2tech\filestorage\hub\Storage]] - allows combination of different file storages.

Please refer to the particular storage class for more details.

**Heads up!** Some of the storages may require additional libraries or PHP extensions, which are not
required with this package by default, to be installed. Please check particulae storage class documentation
for the details.


## Accessing files by URL <span id="accessing-files-by-url"></span>


## Processing of the large files <span id="processing-of-the-large-files"></span>


## Logging <span id="logging"></span>
