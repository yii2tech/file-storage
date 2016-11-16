<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage;

/** 
 * BucketInterface is an interface for the all file storage buckets.
 * All buckets should be controlled by the instance of [[StorageInterface]].
 *
 * @see StorageInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface BucketInterface
{
    /**
     * Sets bucket name.
     * @param string $name - bucket name.
     * @return bool success.
     */
    public function setName($name);

    /**
     * Gets current bucket name.
     * @return string $name - bucket name.
     */
    public function getName();

    /**
     * Sets bucket file storage.
     * @param StorageInterface $storage - file storage.
     * @return bool success.
     */
    public function setStorage(StorageInterface $storage);

    /**
     * Gets bucket file storage.
     * @return StorageInterface - bucket file storage.
     */
    public function getStorage();

    /**
     * Creates this bucket.
     * @return bool success.
     */
    public function create();

    /**
     * Destroys this bucket.
     * @return bool success.
     */
    public function destroy();

    /**
     * Checks is bucket exists.
     * @return bool success.
     */
    public function exists();

    /**
     * Saves content as new file.
     * @param string $fileName - new file name.
     * @param string $content - new file content.
     * @return bool success.
     */
    public function saveFileContent($fileName, $content);

    /**
     * Returns content of an existing file.
     * @param string $fileName - new file name.
     * @return string $content - file content.
     */
    public function getFileContent($fileName);

    /**
     * Deletes an existing file.
     * @param string $fileName - new file name.
     * @return bool success.
     */
    public function deleteFile($fileName);

    /**
     * Checks if the file exists in the bucket.
     * @param string $fileName - searching file name.
     * @return bool file exists.
     */
    public function fileExists($fileName);

    /**
     * Copies file from the OS file system into the bucket.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return bool success.
     */
    public function copyFileIn($srcFileName, $fileName);

    /**
     * Copies file from the bucket into the OS file system.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return bool success.
     */
    public function copyFileOut($fileName, $destFileName);

    /**
     * Copies file inside this bucket or between this bucket and another
     * bucket of this file storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $srcFile - this bucket existing file name or array reference to another bucket file name.
     * @param mixed $destFile - this bucket existing file name or array reference to another bucket file name.
     * @return bool success.
     */
    public function copyFileInternal($srcFile, $destFile);

    /**
     * Copies file from the OS file system into the bucket and
     * deletes the source file.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return bool success.
     */
    public function moveFileIn($srcFileName, $fileName);

    /**
     * Copies file from the bucket into the OS file system and
     * deletes the source bucket file.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return bool success.
     */
    public function moveFileOut($fileName, $destFileName);

    /**
     * Moves file inside this bucket or between this bucket and another
     * bucket of this file storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $srcFile - this bucket existing file name or array reference to another bucket file name.
     * @param mixed $destFile - this bucket existing file name or array reference to another bucket file name.
     * @return bool success.
     */
    public function moveFileInternal($srcFile, $destFile);

    /**
     * Gets web URL of the file.
     * @param string $fileName - self file name.
     * @return string file web URL.
     */
    public function getFileUrl($fileName);

    /**
     * Opens a file as stream resource, e.g. like `fopen()` function.
     * @param string $fileName - file name.
     * @param string $mode - the type of access you require to the stream, e.g. `r`, `w`, `a` and so on.
     * You should prefer usage of simple modes like `r` and `w`, avoiding complex ones like `w+`, as they
     * may not supported by some storages.
     * @param resource|null $context - stream context to be used.
     * @return resource file pointer resource on success, or `false` on error.
     * @since 1.1.0
     */
    public function openFile($fileName, $mode, $context = null);
}