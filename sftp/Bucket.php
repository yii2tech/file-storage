<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\sftp;

use yii\log\Logger;
use yii2tech\filestorage\BucketSubDirTemplate;

/**
 * Bucket introduces the file storage bucket based simply on the SSH2 SFTP
 *
 * @see Storage
 *
 * @property string $baseSubPath sub path in the directory specified by [[Storage::basePath]].
 * @method Storage getStorage()
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.1.0
 */
class Bucket extends BucketSubDirTemplate
{
    /**
     * @var string sub path in the directory specified by [[Storage::basePath]].
     */
    private $_baseSubPath;


    /**
     * @param string $baseSubPath sub path
     */
    public function setBaseSubPath($baseSubPath)
    {
        $this->_baseSubPath = $baseSubPath;
    }

    /**
     * @return string sub path
     */
    public function getBaseSubPath()
    {
        if ($this->_baseSubPath === null) {
            $this->_baseSubPath = $this->defaultBaseSubPath();
        }
        return $this->_baseSubPath;
    }

    /**
     * Composes base sub path with default value.
     * @return string sub path.
     */
    protected function defaultBaseSubPath()
    {
        return $this->getName();
    }

    /**
     * Composes the full file system name of the file.
     * @param string $fileName self name of the file.
     * @param bool $createPath whether to create directory path to the file or not.
     * @return string full file name.
     */
    protected function prepareSftpFileName($fileName, $createPath = true)
    {
        $fullFileName = $this->composeFullFileName($fileName);
        if ($createPath) {
            $this->createDirectory(dirname($fullFileName));
        }
        return $this->composeSftpPath($fullFileName);
    }

    /**
     * Composes SFTP file path according to the SSH2/SFTP stream wrapper format.
     * @param string $fileName file name.
     * @return string SFTP file path.
     */
    protected function composeSftpPath($fileName)
    {
        $sftp = $this->getStorage()->getSftp();
        return 'ssh2.sftp://' . $sftp . $fileName;
    }

    /**
     * Composes the full file system name of the file.
     * @param string $fileName - base name of the file.
     * @return string full file name.
     */
    protected function composeFullFileName($fileName)
    {
        return $this->getFullBasePath() . '/' . $this->getFileNameWithSubDir($fileName);
    }

    /**
     * Returns the bucket full base path.
     * This path is based on [[Storage::basePath]] and [[baseSubPath]].
     * @return string bucket full base path.
     */
    public function getFullBasePath()
    {
        $fullBasePath = $this->getStorage()->basePath . '/' . $this->getBaseSubPath();
        $fullBasePath = rtrim($fullBasePath, '/');
        return $fullBasePath;
    }

    /**
     * Creates a new directory.
     * @param string $path directory path to be created.
     * @return bool whether the directory is created successfully
     */
    protected function createDirectory($path)
    {
        $storage = $this->getStorage();
        $sftp = $storage->getSftp();

        if (file_exists($this->composeSftpPath($path))) {
            return true;
        }
        $parentPath = dirname($path);
        // recurse if parent dir does not exist and we are not at the root of the file system.
        if (!file_exists($this->composeSftpPath($parentPath)) && $parentPath !== $path) {
            $this->createDirectory($parentPath);
        }

        if (!ssh2_sftp_mkdir($sftp, $path, $storage->filePermission)) {
            return false;
        }

        return ssh2_sftp_chmod($sftp, $path, $storage->filePermission);
    }

    /**
     * Removes a directory (and all its content) recursively.
     * @param string $path directory path to be removed.
     */
    protected function removeDirectory($path)
    {
        if (!file_exists($this->composeSftpPath($path))) {
            return;
        }

        $this->getStorage()->ssh->execute('rm -rf ' . escapeshellarg($path));
    }

    /**
     * Gets full file name of the file inside the bucket or inside the other bucket in
     * the same storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param array|string $fileReference - this bucket existing file name or array reference to another bucket file name.
     * @return string full file name.
     */
    protected function composeFullFileNameByReference($fileReference)
    {
        if (is_array($fileReference)) {
            list($bucketName, $fileName) = $fileReference;
            $srcBucket = $this->getStorage()->getBucket($bucketName);
            return $srcBucket->composeFullFileName($fileName);
        }
        return $this->composeFullFileName($fileReference);
    }

    // Bucket Interface :

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return $this->createDirectory($this->getFullBasePath());
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        $this->removeDirectory($this->getFullBasePath());
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        return $this->fileExists('');
    }

    /**
     * {@inheritdoc}
     */
    public function saveFileContent($fileName, $content)
    {
        $sftpFileName = $this->prepareSftpFileName($fileName);
        $writtenBytesCount = file_put_contents($sftpFileName, $content);
        $result = ($writtenBytesCount > 0);
        if ($result) {
            $this->log("file '{$sftpFileName}' has been saved");
            ssh2_sftp_chmod($this->getStorage()->getSftp(), $sftpFileName, $this->getStorage()->filePermission);
        } else {
            $this->log("Unable to save file '{$sftpFileName}'!", Logger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileContent($fileName)
    {
        $fullFileName = $this->prepareSftpFileName($fileName);
        $this->log("content of file '{$fullFileName}' has been returned");
        return file_get_contents($fullFileName);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile($fileName)
    {
        $fullFileName = $this->composeFullFileName($fileName);

        $result = ssh2_sftp_unlink($this->getStorage()->getSftp(), $fullFileName);
        if ($result) {
            $this->log("file '{$fullFileName}' has been deleted");
        } else {
            $this->log("unable to delete file '{$fullFileName}'!", Logger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists($fileName)
    {
        $fullFileName = $this->composeFullFileName($fileName);

        // usage of `file_exists()` may be unreliable - use shell command instead :
        $command = "([ -f " . escapeshellarg($fullFileName) . " ] || [ -d " . escapeshellarg($fullFileName) . " ]) && echo '1' || echo '0'";
        $output = $this->getStorage()->ssh->execute($command);
        $output = trim($output, " \n\r\t");

        return $output === '1';
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileIn($srcFileName, $fileName)
    {
        $sftpFileName = $this->prepareSftpFileName($fileName);
        $result = copy($srcFileName, $sftpFileName);
        if ($result) {
            $this->log("file '{$srcFileName}' has been copied to '{$sftpFileName}'");
            ssh2_sftp_chmod($this->getStorage()->getSftp(), $sftpFileName, $this->getStorage()->filePermission);
        } else {
            $this->log("unable to copy file from '{$srcFileName}' to '{$sftpFileName}'!", Logger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileOut($fileName, $destFileName)
    {
        $sftpFileName = $this->prepareSftpFileName($fileName);
        $result = copy($sftpFileName, $destFileName);
        if ($result) {
            $this->log("file '{$sftpFileName}' has been copied to '{$destFileName}'");
        } else {
            $this->log("unable to copy file from '{$sftpFileName}' to '{$destFileName}'!", Logger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function copyFileInternal($srcFile, $destFile)
    {
        $srcFullFileName = $this->composeFullFileNameByReference($srcFile);
        $srcSftpFileName = $this->composeSftpPath($srcFullFileName);
        $destFullFileName = $this->composeFullFileNameByReference($destFile);
        $destSftpFileName = $this->composeSftpPath($destFullFileName);

        $this->createDirectory(dirname($destFullFileName));
        $result = copy($srcSftpFileName, $destSftpFileName);

        if ($result) {
            $this->log("file '{$srcSftpFileName}' has been copied to '{$destSftpFileName}'");
            ssh2_sftp_chmod($this->getStorage()->getSftp(), $destSftpFileName, $this->getStorage()->filePermission);
        } else {
            $this->log("unable to copy file from '{$srcSftpFileName}' to '{$destSftpFileName}'!", Logger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileIn($srcFileName, $fileName)
    {
        return ($this->copyFileIn($srcFileName, $fileName) && unlink($srcFileName));
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileOut($fileName, $destFileName)
    {
        return ($this->copyFileOut($fileName, $destFileName) && $this->deleteFile($fileName));
    }

    /**
     * {@inheritdoc}
     */
    public function moveFileInternal($srcFile, $destFile)
    {
        $srcFullFileName = $this->composeFullFileNameByReference($srcFile);
        $destFullFileName = $this->composeFullFileNameByReference($destFile);

        $this->createDirectory(dirname($destFullFileName));
        $result = ssh2_sftp_rename($this->getStorage()->getSftp(), $srcFullFileName, $destFullFileName);

        if ($result) {
            $this->log("file '{$srcFullFileName}' has been moved to '{$destFullFileName}'");
        } else {
            $this->log("unable to move file from '{$srcFullFileName}' to '{$destFullFileName}'!", Logger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function openFile($fileName, $mode, $context = null)
    {
        return fopen($this->prepareSftpFileName($fileName), $mode);
    }
}