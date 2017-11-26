<?php
/**
 * Created by PhpStorm.
 * User: naffiq
 * Date: 9/30/2016
 * Time: 4:13 PM
 */

namespace naffiq\kkb;

use LaLit\Array2XML;
use yii\base\Model;

/**
 * Class KKBPaymentResult
 * @package app\modules\payment\components
 */
class KKBPaymentResult extends Model
{
    /**
     * @var string Bank name
     */
    public $bankName;

    /**
     * @var string
     */
    public $customerName;
    /**
     * @var string
     */
    public $customerEmail;
    /**
     * @var string
     */
    public $customerPhone;

    /**
     * @var string Серийный номер сертификата
     */
    public $merchantCertId;
    /**
     * @var string имя магазина(сайта)
     */
    public $merchantName;

    /**
     * @var int Номер заказа(должен состоять не менее чем из 6 ЧИСЛОВЫХ знаков, максимально -15)
     */
    public $orderId;
    /**
     * @var int сумма заказа
     */
    public $orderAmount;
    /**
     * @var int код валюты оплаты [ 398 - тенге ]
     */
    public $orderCurrency;

    /**
     * @var string время проведения платежа
     */
    public $paymentDate;

    /**
     * @var int ID продавца в платежной системе
     */
    public $merchantId;
    /**
     * @var string номер обращения к платежной системе
     */
    public $paymentReference;
    /**
     * @var string код авторизации
     */
    public $paymentApprovalCode;
    /**
     * @var string код результата авторизации. Должен иметь значение "00" (два нуля),
     * в противном случае свяжитесь, пожалуйста, с администратором системы авторизации
     */
    public $paymentResponseCode;
    /**
     * @var bool Yes/No признак, что транзакция была 3DSecure или нет
     */
    public $paymentSecure;
    /**
     * @var string Страна эмитент карты
     */
    public $paymentCardBin;
    /**
     * @var string Хэш карты
     */
    public $paymentCHash;

    /**
     * @var bool
     */
    public $paymentSuccessful;

    /**
     * @var string
     */
    public $paymentErrorType;

    /**
     * @var bool
     */
    public $signErrors = false;

    /**
     * @param array $response
     * @param string $originalResponse
     * @param $kkbComponent bool|KKBPayment
     * @return KKBPaymentResult
     */
    public static function parseSuccessData($response, $originalResponse, KKBPayment $kkbComponent = null)
    {
        $object = new static();
        $object->paymentSuccessful = true;
        $object->checkSignErrors($response, $originalResponse, $kkbComponent);

        $data = $response['document']['bank'];
        $object->bankName = $data['@attributes']['name'];
        $object->paymentDate = $data['results']['@attributes']['timestamp'];

        $object->setCustomerAttributes($data['customer']['@attributes']);
        $object->setMerchantAttributes($data['customer']['merchant']['@attributes']);

        $orderData = $data['customer']['merchant']['order'];
        $object->merchantId = $orderData['department']['@attributes']['merchant_id'];

        $object->setOrderAttributes($orderData['@attributes']);
        $object->setPaymentAttributes($data['results']['payment']['@attributes']);

        return $object;
    }

    public function checkSignErrors($response, $originalResponse, KKBPayment $kkbComponent = null)
    {
        $bankXmlStart = strpos($originalResponse, '<bank');
        $bankXmlLength = strpos($originalResponse, '</bank>') - $bankXmlStart + strlen('</bank>');
        $bankXml = substr($originalResponse, $bankXmlStart, $bankXmlLength);

//        $bankXml = str_replace(' ', '', $bankXml);
//        $bankXml = '<bank name="Kazkommertsbank JSC"><customer name="TSET TEST" mail="abdu.galymzhan@gmail.com" phone=""><merchant cert_id="00C182B189" name="Test shop"><order order_id="400124" amount="1000" currency="398"><department merchant_id="92061101" amount="1000"/></order></merchant><merchant_sign type="RSA"/></customer><customer_sign type="RSA"/><results timestamp="2017-11-24 19:08:56"><payment merchant_id="92061101" card="440564-XX-XXXX-6150" amount="1000" reference="171124190855" approval_code="190855" response_code="00" Secure="No" card_bin="" c_hash="13988BBF7C6649F799F36A4808490A3E"/></results></bank>';
//                    <bank name="Kazkommertsbank JSC"><customer name="TSET TEST" mail="abdu.galymzhan@gmail.com" phone=""><merchant cert_id="00C182B189" name="Test shop"><order order_id="400124" amount="1000" currency="398"><department merchant_id="92061101" amount="1000"></department></order></merchant><merchant_sign type="RSA"></merchant_sign></customer><customer_sign type="RSA"></customer_sign><results timestamp="2017-11-24 19:08:56"><payment merchant_id="92061101" card="440564-XX-XXXX-6150" amount="1000" reference="171124190855" approval_code="190855" response_code="00" Secure="No" card_bin="" c_hash="13988BBF7C6649F799F36A4808490A3E"></payment></results></bank>
        $bankSign = $response['document']['bank_sign']['@value'];
        $kkb = $kkbComponent ?: \Yii::$app->get('kkbPayment');

        $kkbSign = new KKBSign();
        $kkbSign->invert();
        $this->signErrors = $kkbSign->checkSign64($bankXml, $bankSign, \Yii::getAlias($kkb->publicKeyPath)) !== 1;
    }

    /**
     * Обработка результата, в случае неудачной оплаты
     *
     * @param $response array Массив с данными об ошибке
     * @return KKBPaymentResult
     */
    public static function parseErrorData($response)
    {
        $object = new static();
        $object->paymentSuccessful = false;

        $object->orderId = $response['response']['@attributes']['order_id'];

        $errorAttributes = $response['response']['error']['@attributes'];
        $object->paymentErrorType = $errorAttributes['type'];
        $object->paymentDate = $errorAttributes['time'];
        $object->paymentResponseCode = $errorAttributes['code'];

        return $object;
    }

    /**
     * Метод для задачи аттрибутов покупателя
     *
     * @param $attributes
     */
    public function setCustomerAttributes($attributes)
    {
        $this->customerName = $attributes['name'];
        $this->customerEmail = $attributes['mail'];
        $this->customerPhone = $attributes['phone'];
    }

    /**
     * Метод для задачи аттрибутов продавца
     *
     * @param $merchantAttributes
     */
    public function setMerchantAttributes($merchantAttributes)
    {
        $this->merchantCertId = $merchantAttributes['cert_id'];
        $this->merchantName = $merchantAttributes['name'];
    }

    /**
     * Метод для задачи аттрибутов заказа
     *
     * @param $orderAttributes
     */
    public function setOrderAttributes($orderAttributes)
    {

        $this->orderId = $orderAttributes['order_id'];
        $this->orderAmount = $orderAttributes['amount'];
        $this->orderCurrency = $orderAttributes['currency'];
    }

    /**
     * @param $paymentResultData
     */
    public function setPaymentAttributes($paymentResultData)
    {
        $this->paymentReference = $paymentResultData['reference'];
        $this->paymentApprovalCode = $paymentResultData['approval_code'];
        $this->paymentResponseCode = $paymentResultData['response_code'];
        $this->paymentSecure = $paymentResultData['Secure'];
        $this->paymentCardBin = $paymentResultData['card_bin'];
        $this->paymentCHash = $paymentResultData['c_hash'];
    }
}
