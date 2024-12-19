# 🚀 Support Components for Yii2 UNIDA Gontor Apps

![License](https://img.shields.io/badge/license-MIT-blue)
![PHP](https://img.shields.io/badge/php-%3E=7.4-brightgreen)
![Yii2](https://img.shields.io/badge/compatible-Yii2-blue)

Welcome to the **Apps Support Components** package! This collection of Yii2 components simplifies authentication and token management for your applications. Whether you need OAuth2 integration, token handling, or application-based authentication, this package has got you covered! 🌟

---

## 📦 Components Overview

### **Setup**
Put this in your params.php or params-local.php
```php
// Previous params codes
'oauth' => [
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',
    'baseurl' => 'https://your-oauth-server.com',
    'redirectUri' => 'https://your-app.com/callback',
],
```

### **Installation**
1. add this to composer.json `"repositories"`
```json
    "repositories": [
        //...
        {
            "type": "vcs",
            "url": "https://github.com/virgantara/Unida-Apps-Support.git"
        }
    ]
```
2. add this to composer.json `"require"`
```json
    "require": {
        //...
        "virgantara/unida-apps-support": "dev-master"        
    }
```
3. Update your composer by running this code
```bash
composer update -vvv
```
4. Open your `config/web.php`, add the following code in `components`
```php
'components' => [
    ...
    'tokenService' => [
        'class' => 'virgantara\components\TokenService',
    ],
    'aplikasi' => [
        'class' => 'virgantara\components\AplikasiAuth',
        'baseurl' => $params['oauth']['baseurl'], 
    ],
    'tokenManager' => [
        'class' => 'virgantara\components\TokenManager',
    ],
    'oauth2' => [
        'class' => 'virgantara\components\OAuth2Client',
        'tokenValidationUrl' => $params['oauth']['baseurl'], // Endpoint for token validation
        'tokenRefreshUrl' => $params['oauth']['baseurl'],
        'client_id' => $params['oauth']['client_id'],
        'client_secret' => $params['oauth']['client_secret'],
    ],
]
```

5. Open your SiteController.php, add the following codes:
```php
    public function actionAuthCallback()
    {
        try {
            $accessToken = Yii::$app->request->get('access_token');
            $refreshToken = Yii::$app->request->get('refresh_token');

            Yii::$app->tokenService->handleAuthCallback($accessToken, $refreshToken);

            return $this->redirect(['site/index']);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    protected function handleException($e)
    {
        Yii::$app->session->setFlash('danger', $e->getMessage());
        return $this->redirect(['site/index']);
    }

    public function actionCallback()
    {
        try {
            $receivedJwt = Yii::$app->request->get('state');
            $authCode = Yii::$app->request->get('code');

            Yii::$app->tokenService->handleCallback($receivedJwt, $authCode);

            return $this->redirect(['site/index']);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
```