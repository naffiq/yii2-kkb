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
            'publicKeyPath' => __DIR__ . './../payment-keys/test_pub.pem',
            'privateKeyPath' => __DIR__ . './../payment-keys/test_prv.pem',
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
            'publicKeyPath' => __DIR__ . './../payment-keys/test_pub.pem',
            'privateKeyPath' => __DIR__ . './../payment-keys/test_prv.pem',
            'privateKeyPassword' => 'nissan',
            'merchantId' => '92061101',
            'merchantCertificateId' => '00C182B189',
            'merchantName' => 'Test shop',
        ]);

        $xmlResponse = file_get_contents(__DIR__ . '/data/payment_success.xml');

        $result = $kkbService->processResponse($xmlResponse);
        $this->assertInstanceOf(\naffiq\kkb\KKBPaymentResult::className(), $result);
        $this->assertTrue($result->paymentSuccessful);
        $this->assertTrue($result->signErrors);

        $this->assertEquals('Kazkommertsbank JSC', $result->bankName);
        $this->assertEquals('John Cardholder', $result->customerName);
        $this->assertEquals('klient@mymail.com', $result->customerEmail);
        $this->assertEquals('223322', $result->customerPhone);

        $this->assertEquals('7269C18D00010000005E', $result->merchantCertId);
        $this->assertEquals('Shop Name', $result->merchantName);
        $this->assertEquals('92061103', $result->merchantId);

        $this->assertEquals('000282', $result->orderId);
        $this->assertEquals('3100', $result->orderAmount);
        $this->assertEquals('398', $result->orderCurrency);

        $this->assertEquals('00', $result->paymentResponseCode);
        $this->assertEquals('2006-11-22 12:20:30 ', $result->paymentDate);
        $this->assertEquals('109600746891', $result->paymentReference);
        $this->assertEquals('730190', $result->paymentApprovalCode);
        $this->assertEquals('KAZ', $result->paymentCardBin);
        $this->assertEquals('No', $result->paymentSecure);
        $this->assertEquals('6A2D7673A8EEF25A2C33D67CB5AAD091', $result->paymentCHash);
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
            'publicKeyPath' => __DIR__ . './../payment-keys/test_pub.pem',
            'privateKeyPath' => __DIR__ . './../payment-keys/test_prv.pem',
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