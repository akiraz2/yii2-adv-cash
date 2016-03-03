<?php

namespace yarcode\advcash\controllers;

use common\models\billing\Invoice;
use yarcode\advcash\actions\ResultAction;
use yarcode\advcash\events\GatewayEvent;
use yarcode\advcash\Merchant;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\Controller;

class GatewayController extends Controller
{
    /** @inheritdoc */
    public $enableCsrfValidation = false;

    /** @var string Your component configuration name */
    public $componentName = 'advCash';

    /** @var Merchant */
    protected $component;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->component = \Yii::$app->get($this->componentName);

        $this->component->on(GatewayEvent::EVENT_PAYMENT_REQUEST, [$this, 'handlePaymentRequest']);
        $this->component->on(GatewayEvent::EVENT_PAYMENT_SUCCESS, [$this, 'handlePaymentSuccess']);
    }

    public function actions()
    {
        return [
            'result' => [
                'class' => ResultAction::className(),
                'componentName' => $this->componentName,
                'redirectUrl' => ['/billing'],
            ],
            'success' => [
                'class' => ResultAction::className(),
                'componentName' => $this->componentName,
                'redirectUrl' => ['/billing'],
                'silent' => true,
            ],
            'failure' => [
                'class' => ResultAction::className(),
                'componentName' => $this->componentName,
                'redirectUrl' => ['/billing'],
                'silent' => true,
            ]
        ];
    }

    /**
     * @param GatewayEvent $event
     * @return bool
     */
    public function handlePaymentRequest($event)
    {
        $invoice = Invoice::findOne(ArrayHelper::getValue($event->gatewayData, 'ac_order_id'));

        if (!$invoice instanceof Invoice ||
            $invoice->status != Invoice::STATUS_NEW ||
            ArrayHelper::getValue($event->gatewayData, 'ac_merchant_amount') != $invoice->amount ||
            ArrayHelper::getValue($event->gatewayData,
                'ac_transaction_status') != Merchant::TRANSACTION_STATUS_COMPLETED ||
            ArrayHelper::getValue($event->gatewayData, 'ac_sci_name') != $this->component->merchantName
        ) {
            return;
        }

        $invoice->debugData = VarDumper::dumpAsString($event->gatewayData);
        $event->invoice = $invoice;
        $event->handled = true;
    }

    /**
     * @param GatewayEvent $event
     * @return bool
     */
    public function handlePaymentSuccess($event)
    {
        /** @var Invoice $invoice */
        $invoice = $event->invoice;

        // TODO: invoice processing goes here
    }
}
