<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\advcash\actions;

use yarcode\advcash\Merchant;
use yii\base\Action;
use yii\base\InvalidConfigException;

class ResultAction extends Action
{
    /** @var string */
    public $componentName;

    /** @var string */
    public $redirectUrl;

    /** @var bool */
    public $silent = false;

    /** @var Merchant */
    private $api;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->api = \Yii::$app->get($this->componentName);
        if (!$this->api instanceof Merchant) {
            throw new InvalidConfigException('Invalid AdvCash component configuration');
        }

        parent::init();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function run()
    {
        try {
            $this->api->processResult(\Yii::$app->request->post());
        } catch (\Exception $e) {
            if (!$this->silent) {
                throw $e;
            }
        }

        if (isset($this->redirectUrl)) {
            return \Yii::$app->response->redirect($this->redirectUrl);
        }
    }
}