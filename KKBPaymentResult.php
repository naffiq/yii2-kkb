<?php
/**
 * Created by PhpStorm.
 * User: naffiq
 * Date: 9/30/2016
 * Time: 4:13 PM
 */

namespace naffiq\kkb;

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
     * @param $response
     * @param $kkbComponent bool|KKBPayment
     * @return KKBPaymentResult
     */
    public static function parseSuccessData($response, $kkbComponent = false)
    {
        if (!$kkbComponent) {
            $kkb = \Yii::$app->get('kkbPayment');
        } else {
            $kkb = $kkbComponent;
        }


        $kkbSign = new KKBSign();
        $kkbSign->invert();

        $data = $response['document']['bank_sign'];

        $check = $kkbSign->checkSign64($data['@attributes']['cert_id'], $data['@value'], \Yii::getAlias($kkb->publicKeyPath));

        $object = new static();
        $object->paymentSuccessful = true;

        if ($check == 1) {
            $object->signErrors = false;
        } elseif ($check == 0) {
            $object->signErrors = true;
        } else {
            $object->signErrors = true;
//            $checkResult = "[SIGN_CHECK_ERROR]: " . $kkbSign->getErrorStatus();
        }

        $data = $response['document']['bank'];
        $object->paymentDate = $data['results']['@attributes']['timestamp'];

        $object->setCustomerAttributes($data['@attributes']['name']);
        $object->setMerchantAttributes($data['customer']['merchant']['@attributes']);

        $orderData = $data['customer']['merchant']['order'];
        $object->merchantId = $orderData['department']['@attributes']['merchant_id'];

        $object->setOrderAttributes($orderData['@attributes']);
        $object->setPaymentAttributes($data['results']['payment']['@attributes']);

        return $object;
    }

    /**
     * Обработка результата, в случае неудачной оплаты
     *
     * @param $response
     * @param bool $kkbComponent
     * @return KKBPaymentResult
     */
    public static function parseErrorData($response, $kkbComponent = false)
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