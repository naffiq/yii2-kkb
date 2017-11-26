<?php

/**
 * Created by PhpStorm.
 * User: naffiq
 * Date: 8/20/2017
 * Time: 10:52 PM
 */
class KKBPaymentTest extends \PHPUnit\Framework\TestCase
{
    public function testNewPayment()
    {
        $kkbService = new \naffiq\kkb\KKBPayment([
            'publicKeyPath' => __DIR__ . '/../payment-keys/test_pub.pem',
            'privateKeyPath' => __DIR__ . '/../payment-keys/test_prv.pem',
            'privateKeyPassword' => 'nissan',
            'merchantId' => '92061101',
            'merchantCertificateId' => '00C182B189',
            'merchantName' => 'Test shop',
        ]);

        $kkbService->processRequest(123123, 10000);
    }

    public function testPaymentSuccessfulResponseParse()
    {
        $kkbService = new \naffiq\kkb\KKBPayment([
            'publicKeyPath' => __DIR__ . '/../payment-keys/kkbca.pem',
            'privateKeyPath' => __DIR__ . '/../payment-keys/test_prv.pem',
            'privateKeyPassword' => 'nissan',
            'merchantId' => '92061101',
            'merchantCertificateId' => '00C182B189',
            'merchantName' => 'Test shop',
        ]);

        $xmlResponse = file_get_contents(__DIR__ . '/data/payment_success.xml');

        $result = $kkbService->processResponse($xmlResponse);
        $this->assertInstanceOf(\naffiq\kkb\KKBPaymentResult::className(), $result);
        $this->assertTrue($result->paymentSuccessful);
        $this->assertFalse($result->signErrors);

        $this->assertEquals('Kazkommertsbank JSC', $result->bankName);
        $this->assertEquals('TSET TEST', $result->customerName);
        $this->assertEquals('abdu.galymzhan@gmail.com', $result->customerEmail);
        $this->assertEquals('', $result->customerPhone);

        $this->assertEquals('00C182B189', $result->merchantCertId);
        $this->assertEquals('Test shop', $result->merchantName);
        $this->assertEquals('92061101', $result->merchantId);

        $this->assertEquals('400124', $result->orderId);
        $this->assertEquals('1000', $result->orderAmount);
        $this->assertEquals('398', $result->orderCurrency);

        $this->assertEquals('00', $result->paymentResponseCode);
        $this->assertEquals('2017-11-24 19:08:56', $result->paymentDate);
        $this->assertEquals('171124190855', $result->paymentReference);
        $this->assertEquals('190855', $result->paymentApprovalCode);
        $this->assertEquals('', $result->paymentCardBin);
        $this->assertEquals('No', $result->paymentSecure);
        $this->assertEquals('13988BBF7C6649F799F36A4808490A3E', $result->paymentCHash);
    }

    public function testWrongPublicKey()
    {
        $kkbService = new \naffiq\kkb\KKBPayment([
            'publicKeyPath' => 'I_DONT_EXIST',
            'privateKeyPath' => __DIR__ . './../payment-keys/test_prv.pem',
            'privateKeyPassword' => 'nissan',
            'merchantId' => '92061101',
            'merchantCertificateId' => '00C182B189',
            'merchantName' => 'Test shop',
        ]);

        $xmlResponse = file_get_contents(__DIR__ . '/data/payment_success.xml');

        $this->expectException(\naffiq\kkb\exceptions\CertificateException::class);
        $this->expectExceptionCode(4);
        $this->expectExceptionMessage('Public key file not found');

        $kkbService->processResponse($xmlResponse);
    }

    public function testPaymentFailureResponseParse()
    {
        $kkbService = new \naffiq\kkb\KKBPayment([
            'publicKeyPath' => __DIR__ . '/../payment-keys/kkbca.pem',
            'privateKeyPath' => __DIR__ . '/../payment-keys/test_prv.pem',
            'privateKeyPassword' => 'nissan',
            'merchantId' => '92061101',
            'merchantCertificateId' => '00C182B189',
            'merchantName' => 'Test shop',
        ]);

        $xmlResponse = file_get_contents(__DIR__ . '/data/payment_failure.xml');

        $result = $kkbService->processResponse($xmlResponse);
        $this->assertInstanceOf(\naffiq\kkb\KKBPaymentResult::className(), $result);
        $this->assertFalse($result->paymentSuccessful);
        $this->assertEquals('123456', $result->orderId);
        $this->assertEquals('system | auth', $result->paymentErrorType);
        $this->assertEquals('2006-11-22 12:20:30', $result->paymentDate);
        $this->assertEquals('00', $result->paymentResponseCode);
    }
}