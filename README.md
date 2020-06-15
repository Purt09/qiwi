# QIWI работа с API #
Данная библиотека поможет вам быстро начать работу с API QIWI 
https://developer.qiwi.com/ru/qiwi-wallet-personal/
## Установка ##
```
composer require Purt09/qiwi "@dev"
```
## Начало работы
Пример, как проверить была ли оплата на кошелек. \
Под оплатой понимаем перевод суммы на кошелек с комментарием, где комментарий - уникальный идентификатор заказа \
$order_id - идентификатор заказа \
$sum - сумма заказа
```
$qiwi_token = '';
$qiwi_phone = '';
$qiwi = new QiwiApi($qiwi_phone, $qiwi_token;
$result = $qiwi->searchPayment($order_id, $sum);
if($result)
    echo 'Оплата прошла успешно';
 ```
Примечание:
Работает только с рублями
 
