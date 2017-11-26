<?php
/**
 * Created by PhpStorm.
 * User: naffiq
 * Date: 9/13/2016
 * Time: 4:27 PM
 */

namespace naffiq\kkb;

use LaLit\Array2XML;
use LaLit\XML2Array;
use yii\base\Component;
use yii\base\Exception;

use Yii;

/**
 * Class KKBPayment
 *
 * Компонент для генерации поля Signed_Order_B64
 *
 * @package app\modules\payment\components
 */
class KKBPayment extends Component
{
    /**
     * @var string расположение приватного ключа для проверки
     */
    public $publicKeyPath;

    /**
     * @var string расположение приватного ключа для подписи
     */
    public $privateKeyPath;

    /**
     * @var string пароль к приватному ключу
     */
    public $privateKeyPassword;

    /**
     * @var integer ID продавца
     */
    public $merchantId;

    /**
     * @var integer ID сертификата продавца
     */
    public $merchantCertificateId;

    /**
     * @var string Название интернет-магазина/портала
     */
    public $merchantName;

    /**
     * Функция, для генерации поля Signed_Order_B64
     *
     * @param integer $orderId - order index - recoded to 6 digit format with leaded zero
     * @param integer $amount - total payment amount
     * @param bool $b64 - flag to encode result in base64 default = true
     * @param int $currencyCode - preferred currency codes 840-USD, 398-Tenge
     *
     * @return string
     * @throws Exception
     */
    public function processRequest($orderId, $amount, $currencyCode = 398, $b64 = true)
    {
        if (is_numeric($orderId)) {
            if ($orderId > 0) {
                $orderId = sprintf("%06d", $orderId);
            } else {
                throw new Exception("Null Order ID");
            };
        } else {
            throw new Exception("Order ID must be number");
        };

        if (strlen($currencyCode) == 0) {
            throw new Exception("Empty Currency code");
        };
        if ($amount == 0) {
            throw new Exception("Nothing to charge");
        };
        if (empty($this->privateKeyPath)) {
            throw new Exception("Path for Private key not found");
        };

        $kkb = new KKBSign();
        $kkb->invert();
        $kkb->loadPrivateKey(Yii::getAlias($this->privateKeyPath), $this->privateKeyPassword);

        $result = $this->generateMerchantXML($orderId, $amount, $currencyCode);

        $result_sign = '<merchant_sign type="RSA">' . $kkb->sign64($result) . '</merchant_sign>';
        $xml = "<document>" . $result . $result_sign . "</document>";

        if ($b64) {
            return base64_encode($xml);
        } else {
            return $xml;
        }
    }

    /**
     * Генерирует XML для данных об оплате
     *
     * @param integer $orderId - order index - recoded to 6 digit format with leaded zero
     * @param integer $amount - total payment amount
     * @param int $currencyCode - preferred currency codes 840-USD, 398-Tenge
     * @return mixed
     */
    private function generateMerchantXML($orderId, $amount, $currencyCode = 398)
    {
        $xml = Array2XML::createXML('merchant', [
            '@attributes' => [
                'cert_id' => $this->merchantCertificateId,
                'name' => $this->merchantName,
            ],
            'order' => [
                '@attributes' => [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'currency' => $currencyCode,
                ],
                'department' => [
                    '@attributes' => [
                        'merchant_id' => $this->merchantId,
                        'amount' => $amount,
                    ]
                ],
            ]
        ])->saveXML();

        return self::trimXML($xml);
    }

    /**
     * Подготовка XML к отправке в ККБ
     *
     * @param $xml string
     *
     * @return string
     */
    private static function trimXML($xml)
    {
        /*
         * Удаляем первую строку сгенерированного файла
         * (<?xml version="1.0" encoding="UTF-8"?>)
         */
        $xml = preg_replace('/^.+\n/', '', $xml);

        /*
         * Удаляем пробелы, табы, символ обрыва строки
         */
        return preg_replace('/^\s+|\n|\r|\s+$/m', '', $xml);
    }

    /**
     * Process incoming XML to array of values with verifying digital-key
     *
     * @param string $response XML response from bank
     * @return null|KKBPaymentResult
     */
    public function processResponse($response) {
        $result = XML2Array::createArray($response);

        if (!empty($result["response"])){
            return KKBPaymentResult::parseErrorData($result);
        };

        if (!empty($result['document'])){
            return KKBPaymentResult::parseSuccessData($result, $response, $this);
        };

        return null;
    }
}
