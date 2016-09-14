<?php

namespace naffiq\kkb;

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
     * Код ошибки
     * @var integer
     */
    private $errorCode;

    /**
     * Сообщение ошибки
     * @var string
     */
    private $errorMessage;

    /**
     * @param $filename
     * @param null $password
     * @return bool|string
     */
    public function loadPrivateKey($filename, $password = NULL)
    {
        $this->errorCode = 0;
        if (!is_file($filename)) {
            $this->errorCode = 4;
            $this->errorMessage = "[KEY_FILE_NOT_FOUND]";
            return false;
        };

        $c = file_get_contents($filename);

        if (strlen(trim($password)) > 0) {
            $privateKey = openssl_get_privatekey($c, $password);
            $this->parseErrors(openssl_error_string());
        } else {
            $privateKey = openssl_get_privatekey($c);
            $this->parseErrors(openssl_error_string());
        };

        if (is_resource($privateKey)) {
            $this->privateKey = $privateKey;
            return $c;
        }

        return false;
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
     * Проверка на наличие ошибок
     *
     * @return bool
     */
    public function hasErrors()
    {
        return $this->errorCode > 0;
    }

    /**
     * Статус ошибки
     *
     * @return mixed
     */
    public function getErrorStatus()
    {
        return $this->errorMessage;
    }

    /**
     * Процесс инверсии строки
     *
     * @param $str
     * @return string
     */
    private function checkReverse($str)
    {
        if ($this->invert == 1) {
            return strrev($str);
        }

        return $str;
    }

    /**
     * @param $str
     * @return string|bool
     */
    private function sign($str)
    {
        if ($this->privateKey) {
            openssl_sign($str, $out, $this->privateKey);
            $out = $this->checkReverse($out);
            //openssl_free_key($this->privateKey);
            return $out;
        }

        return false;
    }

    /**
     *
     * @param $data
     * @param $str
     * @param $filename
     * @return int
     */
    private function checkSign($data, $str, $filename)
    {
        $str = $this->checkReverse($str);

        if (!is_file($filename)) {
            $this->errorCode = 4;
            $this->errorMessage = "[KEY_FILE_NOT_FOUND]";
            return 2;
        };
        $this->publicKey = file_get_contents($filename);

        $publicKeyId = openssl_get_publickey($this->publicKey);
        $this->parseErrors(openssl_error_string());

        if (is_resource($publicKeyId)) {
            $result = openssl_verify($data, $str, $publicKeyId);
            $this->parseErrors(openssl_error_string());
            openssl_free_key($publicKeyId);
            return $result;
        };

        return 3;
    }

    /**
     * @param $data
     * @param $str
     * @param $filename
     * @return int
     */
    function checkSign64($data, $str, $filename)
    {
        return $this->checkSign($data, base64_decode($str), $filename);
    }

    /**
     * Parses error to error code and message
     *
     * error:0906D06C - Error reading Certificate. Verify Cert type.
     * error:06065064 - Bad decrypt. Verify your Cert password or Cert type.
     * error:0906A068 - Bad password read. Maybe empty password.
     *
     * @param $error
     */
    private function parseErrors($error)
    {
        if (strlen($error) > 0) {
            if (strpos($error, "error:0906D06C") > 0) {
                $this->errorCode = 1;
                $this->errorMessage = "Error reading Certificate. Verify Cert type.";
            };
            if (strpos($error, "error:06065064") > 0) {
                $this->errorCode = 2;
                $this->errorMessage = "Bad decrypt. Verify your Cert password or Cert type.";
            };
            if (strpos($error, "error:0906A068") > 0) {
                $this->errorCode = 3;
                $this->errorMessage = "Bad password read. Maybe empty password.";
            };
            if ($this->errorCode = 0) {
                $this->errorCode = 255;
                $this->errorMessage = $error;
            };
        };
    }
}