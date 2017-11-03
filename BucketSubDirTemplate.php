<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage;

use yii\base\Exception;

/**
 * BucketSubDirTemplate improves the [[BaseBucket]] bucket base class,
 * allowing to specify template for the dynamic file sub directories.
 *
 * @see BaseBucket
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BucketSubDirTemplate extends BaseBucket
{
    /**
     * @var string template of all sub directories, which will store a particular
     * file. Value of this parameter will be parsed per each file.
     * This template may be used to create sub directories avoiding storing too many
     * files at the single directory.
     * To use a dynamic value of the file attribute, place attribute name in curly brackets,
     * for example: {name}.
     * Allowed placeholders:
     * {name} - name of the file,
     * {ext} - extension of the file.
     * You may place symbols "^" before any placeholder name, such placeholder will be resolved as single
     * symbol of the normal value. Number of symbol determined by count of "^".
     * For example:
     * if file name equal to 54321.tmp, placeholder {^name} will be resolved as "5", {^^name} - as "4" and so on.
     * Example value:
     *
     * ```
     * '{^name}/{^^name}'
     * ```
     */
    public $fileSubDirTemplate = '';

    /**
     * @var array internal cache data.
     * This field is for the internal usage only.
     */
    protected $_internalCache = [];


     /**
     * Clears internal cache data.
     * @return bool success.
     */
    public function clearInternalCache()
    {
        $this->_internalCache = [];
        return true;
    }

    /**
     * Gets file storage sub dirs path, resolving [[subDirTemplate]].
     * @param string $fileName - name of the file.
     * @return string file sub dir value.
     */
    protected function getFileSubDir($fileName)
    {
        $subDirTemplate = $this->fileSubDirTemplate;
        if (empty($subDirTemplate)) {
            return $subDirTemplate;
        }
        $this->_internalCache['getFileSubDirFileName'] = $fileName;
        $result = preg_replace_callback("/{(\^*(\w+))}/", [$this, 'getFileSubDirPlaceholderValue'], $subDirTemplate);
        unset($this->_internalCache['getFileSubDirFileName']);
        return $result;
    }

    /**
     * Internal callback function for [[getFileSubDir()]].
     * @param array $matches set of regular expression matches.
     * @throws Exception on failure.
     * @return string value of the placeholder.
     */
    protected function getFileSubDirPlaceholderValue($matches)
    {
        $placeholderName = $matches[1];
        $placeholderPartSymbolPosition = strspn($placeholderName, '^') - 1;
        if ($placeholderPartSymbolPosition >= 0) {
            $placeholderName = $matches[2];
        }

        $fileName = $this->_internalCache['getFileSubDirFileName'];

        switch ($placeholderName) {
            case 'name': {
                $placeholderValue = $fileName;
                break;
            }
            case 'ext':
            case 'extension': {
                $placeholderValue = pathinfo($fileName, PATHINFO_EXTENSION);
                break;
            }
            default: {
                throw new Exception("Unable to resolve file sub dir: unknown placeholder '{$placeholderName}'!");
            }
        }

        $defaultPlaceholderValue = '0';

        if ($placeholderPartSymbolPosition >= 0) {
            if ($placeholderPartSymbolPosition < strlen($placeholderValue)) {
                $placeholderValue = substr($placeholderValue, $placeholderPartSymbolPosition, 1);
            } else {
                $placeholderValue = $defaultPlaceholderValue;
            }
        }

        if (strlen($placeholderValue) <= 0 || in_array($placeholderValue, ['.'])) {
            $placeholderValue = $defaultPlaceholderValue;
        }
        return $placeholderValue;
    }

    /**
     * Returns the file name, including path resolved from [[fileSubDirTemplate]].
     * @param string $fileName - name of the file.
     * @return string name of the file including sub path.
     */
    public function getFileNameWithSubDir($fileName)
    {
        $fileSubDir = $this->getFileSubDir($fileName);
        if (!empty($fileSubDir)) {
            $fullFileName = $fileSubDir . '/' . $fileName;
        } else {
            $fullFileName = $fileName;
        }
        return $fullFileName;
    }

    /**
     * {@inheritdoc}
     */
    protected function composeFileUrl($baseUrl, $fileName)
    {
        $baseUrl = $baseUrl . '/' . urlencode($this->getName());

        $fileSubDir = $this->getFileSubDir($fileName);
        if (!empty($fileSubDir)) {
            $baseUrl .= '/' . $fileSubDir;
        }

        return $baseUrl . '/' . $fileName;
    }
}