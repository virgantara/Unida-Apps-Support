<?php

namespace virgantara\components;

use yii\base\Widget;
use yii\helpers\Html;

class MenuWidget extends Widget
{
    public $accessToken;
    public $refreshToken;
    public $ulClass = 'dropdown-menu';
    public $liClass = 'dropdown';

    public function init()
    {
        parent::init();
        $this->setViewPath(__DIR__ . '/views');
    }

    /**
     * Runs the widget and renders the view with allowed applications.
     *
     * @return string
     */
    public function run()
    {
        // Call the AplikasiAuth component's method
        $allowedApps = Yii::$app->aplikasiAuth->getAllowedAplikasi($this->accessToken, $this->refreshToken);

        return $this->render('menu', [
            'items' => $allowedApps,
            'ulClass' => $this->ulClass,
            'liClass' => $this->liClass
        ]);
    }
}
