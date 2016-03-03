<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\advcash;

use yii\bootstrap\Widget;
use yii\web\View;

class RedirectForm extends Widget
{
    public $viewFile = 'redirect';

    /** @var string Redirect message */
    public $redirectMessage = 'Now you will be redirected to the payment system.';

    /** @var Merchant */
    public $api;
    /** @var integer */
    public $invoiceId;
    /** @var float */
    public $amount;
    /** @var string */
    public $description;

    public $formId = 'adv-cash-checkout-form';

    protected $formAction = 'https://wallet.advcash.com/sci/';
    protected $formMethod = 'POST';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        assert(isset($this->api));
        assert(isset($this->invoiceId));
        assert(isset($this->amount));
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->view->registerJs("$('#{$this->formId}').submit();", View::POS_READY);

        return $this->render($this->viewFile, [
            'formId' => $this->formId,
            'formAction' => $this->formAction,
            'formMethod' => $this->formMethod,

            'redirectMessage' => $this->redirectMessage,
            'api' => $this->api,

            'accountEmail' => $this->api->accountEmail,
            'merchantName' => $this->api->merchantName,
            'amount' => Merchant::normalizeAmount($this->amount),
            'currency' => $this->api->sciCurrency,
            'invoiceId' => $this->invoiceId,
            'sign' => $this->api->sciCheckSign
                ? $this->api->createSciSign($this->amount, $this->invoiceId)
                : null,
            'defaultPaymentSystem' => $this->api->sciDefaultPs,
            'description' => $this->description,

            'successUrl' => $this->api->successUrl,
            'successUrlMethod' => $this->api->successUrlMethod,
            'failureUrl' => $this->api->failureUrl,
            'failureUrlMethod' => $this->api->failureUrlMethod,
            'resultUrl' => $this->api->resultUrl,
            'resultUrlMethod' => $this->api->resultUrlMethod
        ]);
    }
}