<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\advcash;

use yii\base\Component;

class Api extends Component
{
    const WSDL_URL = 'https://wallet.advcash.com:8443/wsm/merchantWebService?wsdl';

    const CARD_TYPE_VIRTUAL = 'VIRTUAL';
    const CARD_TYPE_PLASTIC = 'PLASTIC';

    const E_CURRENCY_BITCOIN = 'BITCOIN';
    const E_CURRENCY_OKPAY = 'OKPAY';
    const E_CURRENCY_PAXUM = 'PAXUM';
    const E_CURRENCY_PAYEER = 'PAYEER';
    const E_CURRENCY_YANDEX_MONEY = 'YANDEX_MONEY';

    const TRANSACTION_NAME_ALL = 'ALL';
    const TRANSACTION_NAME_CHECK_DEPOSIT = 'CHECK_DEPOSIT';
    const TRANSACTION_NAME_WIRE_TRANSFER_DEPOSIT = 'WIRE_TRANSFER_DEPOSIT';
    const TRANSACTION_NAME_WIRE_TRANSFER_WITHDRAW = 'WIRE_TRANSFER_WITHDRAW';
    const TRANSACTION_NAME_INNER_SYSTEM = 'INNER_SYSTEM';
    const TRANSACTION_NAME_CURRENCY_EXCHANGE = 'CURRENCY_EXCHANGE';
    const TRANSACTION_NAME_BANK_CARD_TRANSFER = 'BANK_CARD_TRANSFER';
    const TRANSACTION_NAME_ADVCASH_CARD_TRANSFER = 'ADVCASH_CARD_TRANSFER';
    const TRANSACTION_NAME_EXTERNAL_SYSTEM_DEPOSIT = 'EXTERNAL_SYSTEM_DEPOSIT';
    const TRANSACTION_NAME_EXTERNAL_SYSTEM_WITHDRAWAL = 'EXTERNAL_SYSTEM_WITHDRAWAL';
    const TRANSACTION_NAME_REPAYMENT = 'REPAYMENT';

    const LANGUAGE_EN = 'en';
    const LANGUAGE_RU = 'ru';

    public $apiName;
    public $apiPassword;

    public $soapOptions = [];

    /** @var \SoapClient */
    protected $soapClient;

    public function init()
    {
        parent::init();

        assert($this->apiName);
        assert($this->apiPassword);

        $this->soapClient = new \SoapClient(static::WSDL_URL, []);
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     * @throws \SoapFault
     */
    protected function call($method, $params)
    {
        try {
            $result = $this->soapClient->{$method}($params);
        } catch (\SoapFault $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function createAuthToken()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        return strtoupper(hash('sha256', implode(':', [
            $this->apiPassword,
            $date->format('Ymd'),
            $date->format('H')
        ])));
    }

    public function validateAccount()
    {

    }

    public function validateAccounts()
    {

    }

    public function validateSendMoney()
    {

    }

    public function validateSendMoneyToAdvCash()
    {

    }

    public function validateSendMoneyToBankCard()
    {

    }

    public function validateSendMoneyToECurrency()
    {

    }

    public function validateCurrencyExchange()
    {

    }

    public function validateSendMoneyToEmail()
    {

    }

    public function validateSendMoneyToBtcE()
    {

    }

    public function sendMoney()
    {

    }

    public function sendMoneyToAdvCashCard()
    {

    }

    public function sendMoneyToBankCard()
    {

    }

    public function sendMoneyToECurrency()
    {

    }

    public function exchangeCurrency()
    {

    }

    public function sendMoneyToEmail()
    {

    }

    public function sendMoneyToBtcE()
    {

    }

    public function history()
    {

    }

    public function findTransaction()
    {

    }

    public function getBalances()
    {
        var_dump($this->call('getBalances', []));
    }

    public function register()
    {

    }
}