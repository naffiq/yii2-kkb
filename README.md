# yii2-kkb
[![Build Status](https://travis-ci.org/naffiq/yii2-kkb.svg?branch=master)](https://travis-ci.org/naffiq/yii2-kkb) [![Test Coverage](https://codeclimate.com/github/naffiq/yii2-kkb/badges/coverage.svg)](https://codeclimate.com/github/naffiq/yii2-kkb/coverage) [![Code Climate](https://codeclimate.com/github/naffiq/yii2-kkb/badges/gpa.svg)](https://codeclimate.com/github/naffiq/yii2-kkb) [![Issue Count](https://codeclimate.com/github/naffiq/yii2-kkb/badges/issue_count.svg)](https://codeclimate.com/github/naffiq/yii2-kkb) 

Компонент для оплаты онлайн через КазКом банк для Yii2.

Перед использованием рекоммендуется ознакомится с работой и циклом оплаты в 
[документации банка](https://testpay.kkb.kz/doc/htm/)

Если нашли ошибки или устаревший код то кидайте в issues.

## Установка

Рекоммендуемый способ установки через [composer](https://getcomposer.org/download/).

```bash
$ composer require naffiq/yii2-kkb
```

## Подключение

Добавьте следующие строки в ваш конфигурационный файл (`app\config\main.php`).
Данные настройки были из документации и по ним можно тестировать оплату на 
тестовых серверах ККБ

```php
<?php
return [
    // your config goes here
    
    'components' => [
        
        // ...
        
        'kkbPayment' => [
            'class' => 'naffiq\kkb\KKBPayment',
            
            // Расположение публичного ключа
            'publicKeyPath' => '@vendor/naffiq/yii2-kkb/payment-keys/test_pub.pem',
            // Расположение приватного ключа
            'privateKeyPath' => '@vendor/naffiq/yii2-kkb/payment-keys/test_prv.pem',
            // Ключевая фраза к приватному ключу
            'privateKeyPassword' => 'nissan',
            
            // ID онлайн-магазина в системе kkb
            'merchantId' => '92061101',
            // ID сертификата онлайн-магазина в системе kkb
            'merchantCertificateId' => '00C182B189',
            // Название магазина
            'merchantName' => 'Test shop',
        ],
    ]
    
    // ...
];

```

## Использование

> Для каждого `merchantId` должны генерироваться уникальные `ORDER_ID`. Так как тестовый `merchantId` для всех один,
  то возможно потребуется придумать уникальный числовой префикс к вашему `ORDER_ID`

Для того чтобы отправить запрос на оплату в epay, необходимо сформировать форму 
со следующими полями:

```php
<?php
/**
 * @var $kkbPayment \naffiq\kkb\KKBPayment 
 */
$kkbPayment = \Yii::$app->get('kkbPayment');

// В случае ошибки в этом методе могут выбрасываться исключения.
// В этом случае нужно курить доку и смотреть конфиги
try {
    $kkbPaymentBase64 = $kkbPayment->processRequest(ORDER_ID, ORDER_PRICE);
} catch (\yii\base\Exception $e) {
    $kkbPaymentBase64 = "";
    // TODO: Обработка ошибки
}

// Выставляем адрес сервера платежей в зависимости от окружения
if (YII_ENV_DEV) {
    $paymentUrl = 'https://testpay.kkb.kz/jsp/process/logon.jsp';   
} else {
    $paymentUrl = 'https://epay.kkb.kz/jsp/process/logon.jsp';
}

?>

<form action="<?= $paymentUrl ?>" id="kkb-payment-form" style="display: none">
    <input type="text" name="Signed_Order_B64" size="100" value="<?= $kkbPaymentBase64 ?>">
    <input type="text" id="em" name="email" size="50" maxlength="50" value="<?= CLINET_EMAIL ?>">
    <input type="text" name="Language" size="50" maxlength="3" value="rus">
    <input type="text" name="BackLink" size="50" maxlength="50" value="<?= RETURN_URL ?>">
    <input type="text" name="PostLink" size="50" maxlength="50" value="<?= PROCESS_RESULT_URL ?>">
</form>

```

## Обработка результата

Для обработки результата создайте новый экшн в контроллере (URL на него должно быть указано в поле PostLink).
После этого вызовите метод ```\naffiq\kkb\KKBPayment::processResponse($response)```, где `$response` - это ответ ККБ.

Пример базовой реализации:
```php
<?php
class PaymentController extends \yii\web\Controller {
    //Controller code

    /**
     *
     */
    public function actionProcessResult()
    {
        /**
         * @var $kkb \naffiq\kkb\KKBPayment
         */
        $kkb = \Yii::$app->get('kkbPayment');

        $response = \Yii::$app->request->post('response');
        $paymentResponse = $kkb->processResponse($response);

        // Обработка $paymentResponse
    }
    
}
?>
```

В результате исполнения обработки будет возвращен объект класса `naffiq\kkb\KKBPaymentResult`, который содержит
 все данные об оплате.
 
