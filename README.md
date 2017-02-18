# AdvCash component for Yii2 #

Payment gateway and api client for [AdvCash](http://yiidreamteam.com/link/adv-cash) service.

## Installation ##

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

    php composer.phar require --prefer-dist yarcode/yii2-adv-cash

or add

    "yarcode/yii2-adv-cash": "*"

to the `require` section of your composer.json.

## Usage ##

### Component configuration ###

Configure `advCash` component in the `components` section of your application.

    'advCash' => [
        'class' => '\yarcode\advcash\Merchant'
        'accountEmail' => null,
        'merchantName' => null,
        'merchantPassword' => null,
        'walletNumber' => null,
        'sciCurrency' => \yarcode\advcash\Merchant::CURRENCY_USD,
        'sciCheckSign' => true,
        'sciDefaultPs' => null,
        'successUrl' => null,
        'failureUrl' => null,
        'resultUrl' => null,
        'successUrlMethod' => null,
        'failureUrlMethod' => null,
        'resultUrlMethod' => null
   ]
    
### Redirecting to the payment system ###

To redirect user to AdvCash site you need to create the page with RedirectForm widget.
User will redirected right after page load.

    <?php echo \yarcode\advcash\RedirectForm::widget([
        'api' => Yii::$app->get('advCash'),
        'invoiceId' => $invoice->id,
        'amount' => $invoice->amount,
        'description' => $invoice->description,
    ]); ?>

### Gateway controller ###

You will need to create controller that will handle result requests from AdvCash service.
Sample controller code:

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
                ArrayHelper::getValue($event->gatewayData, 'ac_amount') != $invoice->amount ||
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


## Licence ##

MIT
    
## Links ##

* [Official site](http://yiidreamteam.com/yii2/adv-cash)
* [Source code on GitHub](https://github.com/yarcode/yii2-adv-cash)
* [Composer package on Packagist](https://packagist.org/packages/yarcode/yii2-adv-cash)
* [AdvCash service](http://yiidreamteam.com/link/adv-cash)
