<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\filestorage\sftp;

use Yii;
use yii\base\Component;
use yii\base\Exception;

/**
 * Connection represents SSH2 connection.
 *
 * @property resource|null $session the current SSH session resource pointer.
 * @property bool $isActive whether the SSH connection is established or not.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.1.0
 */
class Connection extends Component
{
    /**
     * @var string SSH hostname or IP address.
     */
    public $host = '127.0.0.1';
    /**
     * @var int SSH port
     */
    public $port = 22;
    /**
     * @var array SSH connection options (methods).
     * For example:
     *
     * ```php
     * [
     *     'kex' => 'diffie-hellman-group1-sha1',
     *     'hostkey' => 'ssh-rsa',
     * ]
     * ```
     *
     * @see http://php.net/manual/en/function.ssh2-connect.php
     */
    public $options = [];
    /**
     * @var string remote user name.
     */
    public $username;
    /**
     * @var string password for the remote user specified by [[username]].
     * If [[privateKeyFile]] is specified this value will be used as key file passphrase.
     */
    public $password;
    /**
     * @var string name of the file, which stores public key in OpenSSH's format.
     * For example:
     *
     * ```php
     * '/home/username/.ssh/id_rsa.pub'
     * '@app/data/ssh/id_rsa.pub'
     * ```
     */
    public $publicKeyFile;
    /**
     * @var string name of the file, which stores private key.
     * For example:
     *
     * ```php
     * '/home/username/.ssh/id_rsa'
     * '@app/data/ssh/id_rsa'
     * ```
     */
    public $privateKeyFile;
    /**
     * @var string expected remote server hostkey hash (fingerprint) to be validation against.
     * If not set - no fingerprint validation will be performed.
     */
    public $hostFingerprint;

    /**
     * @var resource SSH2 session resource pointer.
     */
    private $_session;


    /**
     * Returns the current SSH session resource pointer.
     * @param bool $open whether to automatically start new session, if has not been started yet.
     * @return resource|null current SSH session.
     */
    public function getSession($open = true)
    {
        if ($open && $this->_session === null) {
            $this->open();
        }
        return $this->_session;
    }

    /**
     * Returns a value indicating whether the SSH connection is established.
     * @return bool whether the SSH connection is established
     */
    public function getIsActive()
    {
        return $this->_session !== null;
    }

    /**
     * Opens connection starting new SSH session.
     */
    public function open()
    {
        $session = @ssh2_connect($this->host, $this->port, $this->options);
        if ($session === false) {
            $lastError = error_get_last();
            throw new Exception("Unable to open SSH connection to host '{$this->host}' via port {$this->port}" . (empty($lastError) ? '' : ': ' . $lastError['message']));
        }

        if ($this->hostFingerprint !== null) {
            $this->verifyFingerprint($session, $this->hostFingerprint);
        }

        $this->authenticate($session);

        $this->_session = $session;
    }

    /**
     * Closes current SSH session.
     */
    public function close()
    {
        if ($this->_session === null) {
            return;
        }
        $this->execute('exit;');
        $this->_session = null;
    }

    /**
     * Verifies remote server fingerprint.
     * @param resource $session SSH session.
     * @param string $expectedFingerprint expected remote server fingerprint
     * @throws Exception if fingerprint missmatches.
     */
    protected function verifyFingerprint($session, $expectedFingerprint)
    {
        $fingerprint = ssh2_fingerprint($session, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
        if (strcmp($expectedFingerprint, $fingerprint) !== 0) {
            throw new Exception("Invalid remote server fingerprint: '{$fingerprint}' does not match expected '{$expectedFingerprint}'");
        }
    }

    /**
     * Authenticates user at remote server.
     * @param resource $session SSH session.
     * @throws Exception if authentication fails.
     */
    protected function authenticate($session)
    {
        if ($this->publicKeyFile !== null || $this->privateKeyFile !== null) {
            if (!ssh2_auth_pubkey_file($session, $this->username, Yii::getAlias($this->publicKeyFile), Yii::getAlias($this->privateKeyFile), $this->password)) {
                throw new Exception("Authentication by SSH key failed for user '{$this->username}'");
            }
            return;
        }

        if (!ssh2_auth_password($session, $this->username, $this->password)) {
            throw new Exception("Authentication by password failed for user '{$this->username}'");
        }
    }

    /**
     * Executes SSH command at remote server.
     * @param string $command SSH command text.
     * @return string command output
     * @throws Exception on failure.
     */
    public function execute($command)
    {
        $stream = ssh2_exec($this->getSession(), $command);
        if ($stream === null) {
            throw new Exception("SSH command '{$command}' failed.");
        }
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

        stream_set_blocking($stream, true);
        stream_set_blocking($errorStream, true);

        $output = stream_get_contents($stream);
        $error = stream_get_contents($errorStream);

        fclose($errorStream);
        fclose($stream);

        if (!empty($error)) {
            throw new Exception("SSH command '{$command}' error: " . $error);
        }

        return $output;
    }
}