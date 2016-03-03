<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 *
 * @var \yii\web\View $this
 * @var \yarcode\advcash\Merchant $api
 *
 * @var string $formAction
 * @var string $formMethod
 *
 * @var string $accountEmail
 * @var string $merchantName
 * @var string $amount
 * @var string $currency
 * @var string $invoiceId
 * @var string|null $sign
 * @var string|null $defaultPaymentSystem
 * @var string|null $description
 *
 * @var string|null $successUrl
 * @var string|null $successUrlMethod
 * @var string|null $failureUrl
 * @var string|null $failureUrlMethod
 * @var string|null $resultUrl
 * @var string|null $resultUrlMethod
 *
 * @var string $redirectMessage string
 */

$inputs = array_filter([
    'ac_account_email' => $accountEmail,
    'ac_sci_name' => $merchantName,
    'ac_amount' => $amount,
    'ac_currency' => $currency,
    'ac_order_id' => $invoiceId,
    'ac_sign' => $sign,
    'ac_ps' => $defaultPaymentSystem,
    'ac_comments' => $description,
    'ac_success_url' => $successUrl,
    'ac_success_url_method' => $successUrlMethod,
    'ac_fail_url' => $failureUrl,
    'ac_fail_url_method' => $failureUrl,
    'ac_status_url' => $resultUrl,
    'ac_status_url_method' => $resultUrlMethod
]);

?>
<div class="adv-cash-checkout">
    <p><?= $redirectMessage ?></p>
    <?= \yii\helpers\Html::beginForm($formAction, $formMethod, [
        'id' => $formId
    ]) ?>
    <?php foreach ($inputs as $name => $value): ?>
        <?= \yii\helpers\Html::hiddenInput($name, $value) ?>
    <?php endforeach; ?>
    <?php \yii\helpers\Html::endForm() ?>
</div>