<?php


namespace purt09\qiwi;


class QiwiApi
{
    private $_phone;
    private $_token;
    private $_url;

    function __construct($phone, $token) {
        $this->_phone = preg_replace("/[^0-9]/", '', $phone);
        $this->_token = $token;
        $this->_url   = 'https://edge.qiwi.com/';
    }
    private function sendRequest($method, array $content = [], $post = false) {
        $ch = curl_init();
        if ($post) {
            curl_setopt($ch, CURLOPT_URL, $this->_url . $method);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->_url . $method . '/?' . http_build_query($content));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->_token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, 1);
    }
    public function getAccount(Array $params = []) {
        return $this->sendRequest('person-profile/v1/profile/current', $params);
    }
    public function getPaymentsHistory(Array $params = []) {
        return $this->sendRequest('payment-history/v1/persons/' . $this->_phone . '/payments', $params);
    }
    public function getPaymentsStats(Array $params = []) {
        return $this->sendRequest('payment-history/v1/persons/' . $this->_phone . '/payments/total', $params);
    }
    public function getBalance() {
        return $this->sendRequest('funding-sources/v1/accounts/current')['accounts'];
    }
    public function getTax($providerId) {
        return $this->sendRequest('sinap/providers/'. $providerId .'/form');
    }
    public function sendMoneyToQiwi(Array $params = []) {
        return $this->sendRequest('sinap/terms/99/payments', $params, 1);
    }
    public function sendMoneyToProvider($providerId, Array $params = []) {
        return $this->sendRequest('sinap/terms/'. $providerId .'/payments', $params, 1);
    }

    /**
     * Проверяет оплату по комментарию и сумме
     * Комментарий не должен когда-либо повторятся, иначе пожет привести к сбоям.
     *
     * @param string $comment Уникальный индентификатор
     * @param float $sum Сумма
     * @param int $currency Валюта платежа
     * @return bool
     */
    public function searchPayment($comment, $sum, $currency = 643)
    {
        $result = false;
        foreach ($this->getAllData($currency) as $item)
            if ($item['sum'] == $sum && $item['comment'] == $comment)
                $result = true;
        return $result;
    }

    /**
     * Генерирует ссылку для перевода
     *
     * ВАЖНО при переводе по нику комментари не автозаполняется !!! Баг в киви
     *
     *
     * @param int $priceAmount  сумма в рублях
     * @param int $priceFraction  сумма в копейках
     * @param string $id  Комментарий - уникальный индентификатор
     * @param int $currency валюта
     * @param string $nickname Надо ли перевод по нику
     * @return string
     */
    public function getLink($priceAmount, $priceFraction, $id, $currency = 643, $nickname = null)
    {
        $data = [
            'amountInteger' => $priceAmount,
            'amountFraction' => $priceFraction,
            'currency' => $currency,
            'extra[\'comment\']' => $id,
            'extra[\'account\']' => $this->_phone,
            'blocked' => [
                '0' => 'comment',
                '1' => 'account',
                '2' => 'sum',
            ],
        ];
        $link = 99;
        if(isset($nickname)) {
            array_push($data, [
                'extra[\'accountType\']' => 'nickname',
            ]);
            $link = 99999;
            $data['extra[\'account\']'] = $nickname;
        }

        return 'https://qiwi.com/payment/form/' . $link . '?' . http_build_query($data) . "\n";
    }

    /**
     * @param int $currency
     * @return array
     */
    private function getAllData($currency)
    {
        $data = $this->getPaymentsHistory([
            'rows' => 50,
            'operation' => 'IN',
        ]);
        if($data == null)
            throw new \RuntimeException('Данные от киви кошелька или его токен некорректны. Покупка невозможна');
        $result = [];
        foreach ($data['data'] as $item) {
            if($item['sum']['currency'] != $currency)
                continue;
            array_push($result, [
                'sum' => $item['sum']['amount'],
                'account' => $item['account'],
                'comment' => $item['comment']
            ]);
        }
        return $result;
    }
}
