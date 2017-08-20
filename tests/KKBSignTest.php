<?php

/**
 * Created by PhpStorm.
 * User: naffiq
 * Date: 8/20/2017
 * Time: 10:26 PM
 */
class KKBSignTest extends \PHPUnit\Framework\TestCase
{
    public function testSuccessLoadPrivateKey()
    {
        $kkbSign = new \naffiq\kkb\KKBSign();
        $this->assertNotFalse($kkbSign->loadPrivateKey(__DIR__ .'/../payment-keys/test_prv.pem', 'nissan'), 'Пароль к приватному ключу - верный');
    }

    public function testWrongPasswordPrivateKey()
    {
        $kkbSign = new \naffiq\kkb\KKBSign();
        $this->expectException(\naffiq\kkb\exceptions\CertificateException::class);
        $this->expectExceptionCode(255);
        $this->expectExceptionMessage('Error while reading private key file. Wrong password?');
        $kkbSign->loadPrivateKey(__DIR__ .'/../payment-keys/test_prv.pem', 'not_nissan');
    }

    public function testWithoutPasswordPrivateKey()
    {
        $kkbSign = new \naffiq\kkb\KKBSign();
        $this->expectException(\naffiq\kkb\exceptions\CertificateException::class);
        $this->expectExceptionCode(255);
        $this->expectExceptionMessage('Error while reading private key file. Wrong password?');
        $kkbSign->loadPrivateKey(__DIR__ .'/../payment-keys/test_prv.pem');
    }

    public function testPrivateKeyDoesNotExist()
    {
        $kkbSign = new \naffiq\kkb\KKBSign();
        $this->expectException(\naffiq\kkb\exceptions\CertificateException::class);
        $this->expectExceptionCode(4);
        $this->expectExceptionMessage("[KEY_FILE_NOT_FOUND]");
        $kkbSign->loadPrivateKey('certificate_that_does_not_exist', 'nissan');
    }

    public function testPublicAsPrivateKey()
    {
        $kkbSign = new \naffiq\kkb\KKBSign();
        $this->expectException(\naffiq\kkb\exceptions\CertificateException::class);
        $this->expectExceptionCode(4);
        $this->expectExceptionMessage("[KEY_FILE_NOT_FOUND]");
        $kkbSign->loadPrivateKey(__DIR__ .'/../payment-keys/test_pub.pem', 'nissan');
    }
}