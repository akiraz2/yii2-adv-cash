<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\advcash;

use yarcode\advcash\events\GatewayEvent;
use yii\base\Component;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

class Merchant extends Component
{
    const LOG_CATEGORY = 'AdvCash';

    // AdvCash internal transaction statuses
    const TRANSACTION_STATUS_PENDING = 'PENDING';
    const TRANSACTION_STATUS_PROCESS = 'PROCESS';
    const TRANSACTION_STATUS_CONFIRMED = 'CONFIRMED';
    const TRANSACTION_STATUS_COMPLETED = 'COMPLETED';
    const TRANSACTION_STATUS_CANCELLED = 'CANCELLED';

    // AdvCash internal currencies
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_RUR = 'RUR';
    const CURRENCY_GBP = 'GBP';

    // AdvCash internal payment systems
    const PAYMENT_SYSTEM_ADVANCED_CASH = 'ADVANCED_CASH';
    const PAYMENT_SYSTEM_ALFACLICK = 'ALFACLICK';
    const PAYMENT_SYSTEM_BITCOIN = 'BITCOIN';
    const PAYMENT_SYSTEM_BTC_E = 'BTC_E';
    const PAYMENT_SYSTEM_ECOIN = 'ECOIN';
    const PAYMENT_SYSTEM_OKPAY = 'OKPAY';
    const PAYMENT_SYSTEM_PAXUM = 'PAXUM';
    const PAYMENT_SYSTEM_PAYEER = 'PAYEER';
    const PAYMENT_SYSTEM_PERFECT_MONEY = 'PERFECT_MONEY';
    const PAYMENT_SYSTEM_PRIVAT24 = 'PRIVAT24';
    const PAYMENT_SYSTEM_PSB_RETAIL = 'PSB_RETAIL';
    const PAYMENT_SYSTEM_QIWI = 'QIWI';
    const PAYMENT_SYSTEM_RUSSIAN_STANDARD_BANK = 'RUSSIAN_STANDARD_BANK';
    const PAYMENT_SYSTEM_SBER_ONLINE = 'SBER_ONLINE';
    const PAYMENT_SYSTEM_SVYAZNOY_BANK = 'SVYAZNOY_BANK';
    const PAYMENT_SYSTEM_YANDEX_MONEY = 'YANDEX_MONEY';

    /** @var string Account email */
    public $accountEmail;
    /** @var string */
    public $walletNumber;

    /** @var string Specified merchantName */
    public $merchantName;
    /** @var string Specified merchant secret */
    public $merchantPassword;
    /** @var string Payment currency */
    public $sciCurrency = self::CURRENCY_USD;
    /** @var bool */
    public $sciCheckSign = true;
    /** @var null Default suggested payment system */
    public $sciDefaultPs = null;

    public $successUrl;
    public $successUrlMethod = 'POST';

    public $failureUrl;
    public $failureUrlMethod = 'POST';

    public $resultUrl;
    public $resultUrlMethod = 'POST';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        assert(isset($this->accountEmail));
        assert(isset($this->merchantName));
        assert(isset($this->merchantPassword));

        $this->resultUrl = $this->resultUrl ? Url::to($this->resultUrl, true) : null;
        $this->successUrl = $this->successUrl ? Url::to($this->successUrl, true) : null;
        $this->failureUrl = $this->failureUrl ? Url::to($this->failureUrl, true) : null;
    }

    /**
     * @param array $data
     * @return bool
     * @throws HttpException
     * @throws \yii\db\Exception
     */
    public function processResult($data)
    {
        if (!$this->checkHash($data)) {
            throw new ForbiddenHttpException('Hash error');
        }

        $event = new GatewayEvent(['gatewayData' => $data]);

        $this->trigger(GatewayEvent::EVENT_PAYMENT_REQUEST, $event);
        if (!$event->handled) {
            throw new HttpException(503, 'Error processing request');
        }

        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            $this->trigger(GatewayEvent::EVENT_PAYMENT_SUCCESS, $event);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            \Yii::error('Payment processing error: ' . $e->getMessage(), static::LOG_CATEGORY);
            throw new HttpException(503, 'Error processing request');
        }

        return true;
    }

    /**
     * Return result of checking SCI hash
     *
     * @param array $data Request array to check, usually $_POST
     * @return bool
     */
    public function checkHash($data)
    {
        if (!isset(
            $data['ac_transfer'],
            $data['ac_start_date'],
            $data['ac_sci_name'],
            $data['ac_src_wallet'],
            $data['ac_dest_wallet'],
            $data['ac_order_id'],
            $data['ac_amount'],
            $data['ac_merchant_currency']
        )
        ) {
            return false;
        }

        $params = [
            $data['ac_transfer'],
            $data['ac_start_date'],
            $data['ac_sci_name'],
            $data['ac_src_wallet'],
            $data['ac_dest_wallet'],
            $data['ac_order_id'],
            $data['ac_amount'],
            $data['ac_merchant_currency'],
            $this->merchantPassword
        ];

        $hash = hash('sha256', implode(':', $params));

        if ($hash == $data['ac_hash']) {
            return true;
        }

        \Yii::error('Hash check failed: ' . VarDumper::dumpAsString($params), static::LOG_CATEGORY);
        return false;
    }

    /**
     * @param $amount
     * @param $invoiceId
     * @return string
     */
    public function createSciSign($amount, $invoiceId)
    {
        return hash('sha256', implode(':', [
            $this->accountEmail,
            $this->merchantName,
            $this::normalizeAmount($amount),
            $this->sciCurrency,
            $this->merchantPassword,
            $invoiceId
        ]));
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
}