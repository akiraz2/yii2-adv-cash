<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\advcash;

use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class Api extends Component
{
    const WSDL_URL = 'https://wallet.advcash.com:8443/wsm/merchantWebService?wsdl';

    const CARD_TYPE_VIRTUAL = 'VIRTUAL';
    const CARD_TYPE_PLASTIC = 'PLASTIC';

    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_RUR = 'RUR';
    const CURRENCY_GBP = 'GBP';

    const E_CURRENCY_BITCOIN = 'BITCOIN';
    const E_CURRENCY_OKPAY = 'OKPAY';
    const E_CURRENCY_PAXUM = 'PAXUM';
    const E_CURRENCY_PAYEER = 'PAYEER';
    const E_CURRENCY_YANDEX_MONEY = 'YANDEX_MONEY';

    const TRANSACTION_STATUS_PENDING = 'PENDING';
    const TRANSACTION_STATUS_PROCESS = 'PROCESS';
    const TRANSACTION_STATUS_CONFIRMED = 'CONFIRMED';
    const TRANSACTION_STATUS_COMPLETED = 'COMPLETED';
    const TRANSACTION_STATUS_CANCELLED = 'CANCELLED';

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

    public $accountEmail;

    public $name;
    public $password;

    public $soapOptions = [
        'location' => 'https://wallet.advcash.com:8443/wsm/merchantWebService'
    ];

    /** @var \SoapClient */
    protected $soapClient;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        assert($this->accountEmail !== null);
        assert($this->name !== null);
        assert($this->password !== null);

        $this->registerClient();
    }

    /**
     * @throws \SoapFault
     */
    protected function registerClient()
    {
        try {
            $this->soapClient = new \SoapClient(static::WSDL_URL, $this->soapOptions);
        } catch (\SoapFault $e) {
            throw $e;
        }
    }

    /**
     * @param $email
     * @param $walletId
     * @param $firstName
     * @param $lastName
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateAccount($email, $walletId, $firstName, $lastName)
    {
        return $this->call('validateAccount', [
            'email' => $email,
            'walletId' => $walletId,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ]);
    }

    /**
     * @param $method
     * @param $params
     * @return \stdClass
     * @throws \SoapFault
     */
    protected function call($method, $params = null)
    {
        try {
            $result = $this->soapClient->{$method}([
                'arg0' => [
                    'apiName' => $this->name,
                    'authenticationToken' => $this->createAuthToken(),
                    'accountEmail' => $this->accountEmail
                ],
                'arg1' => $params
            ]);
        } catch (\SoapFault $e) {
            throw $e;
        }

        return static::processResult($result);
    }

    /**
     * @return string
     */
    public function createAuthToken()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        return strtoupper(hash('sha256', implode(':', [
            $this->password,
            $date->format('Ymd'),
            $date->format('H')
        ])));
    }

    /**
     * Convert stdObject to array
     * @param $result
     * @return mixed
     */
    protected static function processResult(\stdClass $result)
    {
        return Json::decode(
            Json::encode(
                ArrayHelper::getValue($result, 'return', [])
            )
        );
    }

    /**
     * @param array $emails
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateAccounts(array $emails)
    {
        return $this->call('validateAccounts', $emails);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $email
     * @param $walletId
     * @param null $note
     * @param bool|false $savePaymentTemplate
     * @return \stdClass
     * @throws \Exception
     * @throws \SoapFault
     */
    public function validateSendMoney($amount, $currency, $email, $walletId, $note = null, $savePaymentTemplate = false)
    {
        return $this->call('validationSendMoney', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'email' => $email,
            'walletId' => $walletId,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * Normalize amount
     * @param $amount
     * @return string
     */
    public static function normalizeAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param $amount
     * @param $currency
     * @param $email
     * @param $cardType
     * @param null $note
     * @param bool $savePaymentTemplate
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateSendMoneyToAdvCashCard(
        $amount,
        $currency,
        $email,
        $cardType,
        $note = null,
        $savePaymentTemplate = false
    ) {
        return $this->call('validationSendMoneyToAdvcashCard', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'email' => $email,
            'cardType' => $cardType,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $cardNumber
     * @param $expiryMonth
     * @param $expiryYear
     * @param null $note
     * @param bool $savePaymentTemplate
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateSendMoneyToBankCard(
        $amount,
        $currency,
        $cardNumber,
        $expiryMonth,
        $expiryYear,
        $note = null,
        $savePaymentTemplate = false
    ) {
        return $this->call('validationSendMoneyToBankCard', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'cardNumber' => $cardNumber,
            'expiryMonth' => $expiryMonth,
            'expiryYear' => $expiryYear,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $eCurrency
     * @param $receiver
     * @param null $note
     * @param bool $savePaymentTemplate
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateSendMoneyToECurrency(
        $amount,
        $currency,
        $eCurrency,
        $receiver,
        $note = null,
        $savePaymentTemplate = false
    ) {
        return $this->call('validationSendMoneyToEcurrency', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'eCurrency' => $eCurrency,
            'receiver' => $receiver,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * @param $from
     * @param $to
     * @param $action
     * @param $amount
     * @param null $note
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateCurrencyExchange($from, $to, $action, $amount, $note = null)
    {
        return $this->call('validationCurrencyExchange', [
            'from' => $from,
            'to' => $to,
            'action' => $action,
            'amount' => static::normalizeAmount($amount),
            'note' => $note,
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $email
     * @param $note
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateSendMoneyToEmail($amount, $currency, $email, $note = null)
    {
        return $this->call('validationSendMoneyToEmail', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'email' => $email,
            'note' => $note
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param null $note
     * @param bool $savePaymentTemplate
     * @return \stdClass
     * @throws \SoapFault
     */
    public function validateSendMoneyToBtcE($amount, $currency, $note = null, $savePaymentTemplate = false)
    {
        return $this->call('validationSendMoneyToBtcE', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $email
     * @param $walletId
     * @param null $note
     * @param bool|false $savePaymentTemplate
     * @return \stdClass
     * @throws \Exception
     * @throws \SoapFault
     */
    public function sendMoney($amount, $currency, $email, $walletId, $note = null, $savePaymentTemplate = false)
    {
        return $this->call('sendMoney', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'email' => $email,
            'walletId' => $walletId,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate,
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $email
     * @param $cardType
     * @param null $note
     * @return \stdClass
     * @throws \SoapFault
     */
    public function sendMoneyToAdvCashCard(
        $amount,
        $currency,
        $email,
        $cardType,
        $note = null
    ) {
        return $this->call('sendMoneyToAdvcashCard', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'email' => $email,
            'cardType' => $cardType,
            'note' => $note
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $cardNumber
     * @param $expiryMonth
     * @param $expiryYear
     * @param null $note
     * @param bool $savePaymentTemplate
     * @return \stdClass
     * @throws \SoapFault
     */
    public function sendMoneyToBankCard(
        $amount,
        $currency,
        $cardNumber,
        $expiryMonth,
        $expiryYear,
        $note = null,
        $savePaymentTemplate = false
    ) {
        return $this->call('sendMoneyToBankCard', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'cardNumber' => $cardNumber,
            'expiryMonth' => $expiryMonth,
            'expiryYear' => $expiryYear,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);

    }

    /**
     * @param $amount
     * @param $currency
     * @param $eCurrency
     * @param $receiver
     * @param null $note
     * @param bool $savePaymentTemplate
     * @return \stdClass
     * @throws \SoapFault
     */
    public function sendMoneyToECurrency(
        $amount,
        $currency,
        $eCurrency,
        $receiver,
        $note = null,
        $savePaymentTemplate = false
    ) {
        return $this->call('sendMoneyToEcurrency', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'eCurrency' => $eCurrency,
            'receiver' => $receiver,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * @param $from
     * @param $to
     * @param $action
     * @param $amount
     * @param null $note
     * @return \stdClass
     * @throws \SoapFault
     */
    public function currencyExchange($from, $to, $action, $amount, $note = null)
    {
        return $this->call('currencyExchange', [
            'from' => $from,
            'to' => $to,
            'action' => $action,
            'amount' => static::normalizeAmount($amount),
            'note' => $note,
        ]);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $email
     * @param null $note
     * @return \stdClass
     * @throws \SoapFault
     */
    public function sendMoneyToEmail($amount, $currency, $email, $note = null)
    {
        return $this->call('sendMoneyToEmail', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'email' => $email,
            'note' => $note
        ]);
    }

    public function sendMoneyToBtcE($amount, $currency, $note = null, $savePaymentTemplate = false)
    {
        return $this->call('sendMoneyToBtcE', [
            'amount' => static::normalizeAmount($amount),
            'currency' => $currency,
            'note' => $note,
            'savePaymentTemplate' => $savePaymentTemplate
        ]);
    }

    /**
     * @param $accountName
     * @param $startTimeFrom
     * @param $startTimeTo
     * @param $transactionName
     * @param $transactionStatus
     * @param $updatedFrom
     * @param $updatedTo
     * @param null $walletId
     * @return \stdClass
     * @throws \SoapFault
     */
    public function history(
        $accountName,
        $startTimeFrom,
        $startTimeTo,
        $transactionName,
        $transactionStatus,
        $updatedFrom,
        $updatedTo,
        $walletId = null
    ) {
        return $this->call('history', [
            'accountName' => $accountName,
            'startTimeFrom' => $startTimeFrom,
            'startTimeTo' => $startTimeTo,
            'transactionName' => $transactionName,
            'transactionStatus' => $transactionStatus,
            'updatedFrom' => $updatedFrom,
            'updatedTo' => $updatedTo,
            'walletId' => $walletId
        ]);
    }

    /**
     * @param $transactionId
     * @return \stdClass
     * @throws \SoapFault
     */
    public function findTransaction($transactionId)
    {
        return $this->call('findTransaction', $transactionId);
    }

    /**
     * @return \stdClass
     * @throws \SoapFault
     */
    public function getBalances()
    {
        return $this->call('getBalances');
    }

    /**
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param $language
     * @param string $ip
     * @return \stdClass
     * @throws \SoapFault
     */
    public function register($email, $firstName, $lastName, $language, $ip = '*.*.*.*')
    {
        return $this->call('register', [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'language' => $language,
            'ip' => $ip,
        ]);
    }
}