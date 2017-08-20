<?php

namespace naffiq\kkb;
use naffiq\kkb\exceptions\CertificateException;

/**
 * Class KKBSign
 *
 * Шифрует данные при помщи ключа в base64 (RSA)
 *
 * @package app\modules\payment\components
 */
class KKBSign
{
    /**
     * Приватный ключ
     * @var
     */
    private $privateKey;
    /**
     * Публичный ключ
     * @var
     */
    private $publicKey;

    /**
     * Отметка для реверса строки
     * @var integer
     */
    private $invert;

    /**
     * @param $filename
     * @param null $password
     * @return bool|string
     * @throws CertificateException
     */
    public function loadPrivateKey($filename, $password = NULL)
    {
        if (!is_file($filename)) {
            throw new CertificateException("Private key file not found", 4);
        };

        $privateKeyFile = file_get_contents($filename);

        if (strlen(trim($password)) > 0) {
            $privateKey = openssl_get_privatekey($privateKeyFile, $password);
            $this->checkErrors(openssl_error_string());
        } else {
            $privateKey = openssl_get_privatekey($privateKeyFile);
            $this->checkErrors(openssl_error_string());
        };

        if (is_resource($privateKey)) {
            $this->privateKey = $privateKey;
            return $privateKeyFile;
        }

        throw new CertificateException('Error while reading private key file. Wrong password?', 255);
    }

    /**
     * Установка флага инверсии
     */
    public function invert()
    {
        $this->invert = 1;
    }

    /**
     * Возвращает закодированную сертификатом строку
     *
     * @param string $str
     * @return string
     */
    public function sign64($str)
    {
        return base64_encode($this->sign($str));
    }

    /**
     * Процесс инверсии строки
     *
     * @param $str
     * @return string
     */
    private function checkReverse($str)
    {
        return $this->invert == 1 ? strrev($str) : $str;
    }

    /**
     * @param $str
     * @return string|bool
     */
    private function sign($str)
    {
        openssl_sign($str, $out, $this->privateKey);
        $out = $this->checkReverse($out);
        openssl_free_key($this->privateKey);
        return $out;
    }

    /**
     *
     * @param $data
     * @param $str
     * @param $filename
     * @return int
     *
     * @throws CertificateException
     */
    private function checkSign($data, $str, $filename)
    {
        $str = $this->checkReverse($str);

        if (!is_file($filename)) {
            throw new CertificateException("Public key file not found", 4);
        };
        $this->publicKey = file_get_contents($filename);

        $publicKeyId = openssl_get_publickey($this->publicKey);
        $this->checkErrors(openssl_error_string());

        if (is_resource($publicKeyId)) {
            $result = openssl_verify($data, $str, $publicKeyId);
            $this->checkErrors(openssl_error_string());
            openssl_free_key($publicKeyId);
            return $result;
        };

        // @codeCoverageIgnoreStart
        throw new CertificateException('Unexpected exception while reading public key file, report with data to https://github.com/naffiq/yii2-kkb');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param $data
     * @param $str
     * @param $filename
     * @return int
     */
    public function checkSign64($data, $str, $filename)
    {
        return $this->checkSign($data, base64_decode($str), $filename);
    }

    /**
     * Проверяет ошибки функции `openssl_error_string()` и выбрасывает исключение класса
     * `naffiq\kkb\CertificateException`
     *
     * error:0906D06C - Error reading Certificate. Verify Cert type.
     * error:06065064 - Bad decrypt. Verify your Cert password or Cert type.
     * error:0906A068 - Bad password read. Maybe empty password.
     *
     * @codeCoverageIgnore
     * @param string|false $error результат функции `openssl_error_string()`
     * @throws CertificateException
     */
    private function checkErrors($error)
    {
        if ($error !== false) {
            if (strpos($error, "error:0906D06C") > 0) {
                throw new CertificateException("Error reading Certificate. Verify Cert type.", 1);
            };
            if (strpos($error, "error:06065064") > 0) {
                throw new CertificateException("Bad decrypt. Verify your Cert password or Cert type.", 2);
            };
            if (strpos($error, "error:0906A068") > 0) {
                throw new CertificateException("Bad password read. Maybe empty password.", 3);
            };
        };
    }
}