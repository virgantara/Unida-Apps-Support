# 🚀 UNIDA Apps Support Components for Yii2

![License](https://img.shields.io/badge/license-MIT-blue)
![PHP](https://img.shields.io/badge/php-%3E=7.4-brightgreen)
![Yii2](https://img.shields.io/badge/compatible-Yii2-blue)

A reusable Yii2 component package for UNIDA Gontor applications. This package provides helper components for OAuth2 authentication, token handling, user session integration, and application jump support between UNIDA apps.

---

## 📦 Features

- OAuth2 Authorization Code integration
- Access token and refresh token management
- User info retrieval from SSO server
- Allowed application list retrieval
- Secure app-to-app jump support
- Yii2 component-based integration

---

## ⚠️ Security Notice

This package must use OAuth2 Authorization Code Flow for authentication and app jumping.

Do **not** send `access_token` or `refresh_token` through URL query parameters.

Bad example:

```text
https://app.example.com/callback?access_token=xxx&refresh_token=yyy
```

Recommended flow:

```text
Source App
  ↓
Target App /site/start-sso
  ↓
SSO /oauth/authorize
  ↓
Target App /site/auth-callback?code=xxx&state=yyy
  ↓
Target App exchanges code for token
```

---

## 📋 Requirements

- PHP >= 7.4
- Yii2 Framework
- Composer
- OAuth2 SSO server

---

## ⚙️ Installation

### 1. Add Repository to `composer.json`

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/virgantara/Unida-Apps-Support.git"
        }
    ]
}
```

### 2. Add Package Requirement

For development branch:

```json
{
    "require": {
        "virgantara/unida-apps-support": "dev-master"
    }
}
```

For stable release tag:

```json
{
    "require": {
        "virgantara/unida-apps-support": "^1.0"
    }
}
```

Then run:

```bash
composer update -vvv
```

Or install directly:

```bash
composer require virgantara/unida-apps-support:^1.0
```

---

## 🔧 OAuth Configuration

Add this configuration to `params.php` or `params-local.php`.

```php
return [
    'oauth' => [
        'client_id' => 'your-client-id',
        'client_secret' => 'your-client-secret',
        'baseurl' => 'https://your-sso-server.com',
        'redirectUri' => 'https://your-app.com/site/auth-callback',
        'scope' => 'read write',
    ],
];
```

Example:

```php
return [
    'oauth' => [
        'client_id' => 'elitabmas',
        'client_secret' => 'your-secret',
        'baseurl' => 'https://sso.unida.gontor.ac.id',
        'redirectUri' => 'https://elitabmas.unida.gontor.ac.id/site/auth-callback',
        'scope' => 'read write',
    ],
];
```

---

## 🧩 Yii2 Component Configuration

Open `config/web.php`, then add the components below.

```php
'components' => [
    // Other components...

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
        'tokenValidationUrl' => $params['oauth']['baseurl'],
        'tokenRefreshUrl' => $params['oauth']['baseurl'],
        'client_id' => $params['oauth']['client_id'],
        'client_secret' => $params['oauth']['client_secret'],
    ],
],
```

---

## 🔐 Start SSO Login

Add this action to your `SiteController`.

```php
public function actionStartSso()
{
    $session = Yii::$app->session;

    if (!$session->isActive) {
        $session->open();
    }

    $state = Yii::$app->security->generateRandomString(40);
    $session->set('oauth_state', $state);

    $params = [
        'client_id' => Yii::$app->params['oauth']['client_id'],
        'redirect_uri' => Yii::$app->params['oauth']['redirectUri'],
        'response_type' => 'code',
        'scope' => Yii::$app->params['oauth']['scope'] ?? 'read write',
        'state' => $state,
    ];

    $authUrl = rtrim(Yii::$app->params['oauth']['baseurl'], '/')
        . '/oauth/authorize?'
        . http_build_query($params);

    return $this->redirect($authUrl);
}
```

---

## 🔁 OAuth Callback

Add this callback action to your `SiteController`.

```php
public function actionAuthCallback()
{
    try {
        $session = Yii::$app->session;

        if (!$session->isActive) {
            $session->open();
        }

        $sessionState = $session->get('oauth_state');
        $receivedState = Yii::$app->request->get('state');

        if (empty($sessionState) || empty($receivedState) || $sessionState !== $receivedState) {
            throw new \yii\web\BadRequestHttpException('Invalid OAuth state.');
        }

        $authCode = Yii::$app->request->get('code');

        if (empty($authCode)) {
            throw new \yii\web\BadRequestHttpException('Authorization code not received.');
        }

        $response = Yii::$app->tokenManager->fetchAccessTokenWithAuthCode($authCode);

        if (empty($response['access_token'])) {
            throw new \yii\web\UnauthorizedHttpException('Failed to get access token.');
        }

        $accessToken = $response['access_token'];
        $refreshToken = $response['refresh_token'] ?? null;

        $userinfo = Yii::$app->tokenManager->getUserinfo($accessToken);

        if (empty($userinfo) || empty($userinfo['email'])) {
            throw new \yii\web\UnauthorizedHttpException('Failed to get user info.');
        }

        $session->set('access_token', $accessToken);

        if (!empty($refreshToken)) {
            $session->set('refresh_token', $refreshToken);
        }

        /*
         * TODO:
         * Implement local user login here.
         *
         * Example:
         * - Find local user by email
         * - Create user if allowed
         * - Login using Yii::$app->user->login($identity)
         */

        return $this->redirect(['site/index']);
    } catch (\Throwable $e) {
        return $this->handleException($e);
    }
}
```

Add the exception handler:

```php
protected function handleException($e)
{
    Yii::error($e->getMessage(), __METHOD__);
    Yii::$app->session->setFlash('danger', $e->getMessage());

    return $this->redirect(['site/index']);
}
```

---

## 🧭 App-to-App Jump

This package supports Google-like app jumping.

Example:

```text
SIAKAD → ELITABMAS
E-KHIDMAH → MOODLE
MOODLE → SIAKAD
```

The source app should only open the target app login endpoint:

```text
https://target-app.unida.gontor.ac.id/site/start-sso
```

The target app will then start its own OAuth2 flow.

Correct flow:

```text
User is logged in to SIAKAD
  ↓
User clicks ELITABMAS
  ↓
Browser opens ELITABMAS /site/start-sso
  ↓
ELITABMAS redirects to SSO /oauth/authorize
  ↓
SSO detects active SSO session
  ↓
SSO redirects back to ELITABMAS callback with authorization code
  ↓
ELITABMAS exchanges code for token
  ↓
User is logged in to ELITABMAS
```

The source application must not generate OAuth state for the target application.

---

## 📱 Allowed Apps List

You can render allowed applications from SSO using:

```php
$apps = Yii::$app->aplikasi->getRenderedAllowedAppsList();
```

Example usage in layout or menu:

```php
use yii\widgets\Menu;

echo Menu::widget([
    'items' => Yii::$app->aplikasi->getRenderedAllowedAppsList(),
    'encodeLabels' => false,
]);
```

The SSO `/app/list` endpoint should return data like this:

```json
[
    {
        "app_id": 1,
        "client_id": "siakad",
        "app_name": "SIAKAD",
        "base_url": "https://siakad.unida.gontor.ac.id",
        "jump_callback": "/site/start-sso"
    },
    {
        "app_id": 2,
        "client_id": "elitabmas",
        "app_name": "ELITABMAS",
        "base_url": "https://elitabmas.unida.gontor.ac.id",
        "jump_callback": "/site/start-sso"
    }
]
```

Or preferably:

```json
[
    {
        "app_id": 2,
        "client_id": "elitabmas",
        "app_name": "ELITABMAS",
        "start_sso_url": "https://elitabmas.unida.gontor.ac.id/site/start-sso"
    }
]
```

---

## 🚪 Logout

Recommended logout flow:

```text
Application logout
  ↓
Clear local Yii2 session
  ↓
Redirect to SSO logout endpoint
  ↓
SSO clears global SSO session
  ↓
Redirect back to application logout callback
```

Example:

```php
public function actionLogout()
{
    Yii::$app->user->logout();

    Yii::$app->session->remove('access_token');
    Yii::$app->session->remove('refresh_token');

    $logoutUrl = rtrim(Yii::$app->params['oauth']['baseurl'], '/')
        . '/oauth/logout?'
        . http_build_query([
            'client_id' => Yii::$app->params['oauth']['client_id'],
        ]);

    return $this->redirect($logoutUrl);
}
```

---

## 🏷️ Release Tag Procedure

Use semantic versioning for releases.

Format:

```text
MAJOR.MINOR.PATCH
```

Examples:

```text
v1.0.0
v1.0.1
v1.1.0
v2.0.0
```

### 1. Check Current Branch

```bash
git branch
```

Switch to `master` or `main` branch:

```bash
git checkout master
```

or:

```bash
git checkout main
```

### 2. Pull Latest Changes

```bash
git pull origin master
```

or:

```bash
git pull origin main
```

### 3. Check Git Status

```bash
git status
```

Make sure there are no uncommitted changes.

### 4. Commit Changes

```bash
git add .
git commit -m "Prepare release v1.0.0"
```

Skip this step if all changes have already been committed.

### 5. Create Git Tag

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
```

### 6. Push Commit and Tag

If using `master`:

```bash
git push origin master
git push origin v1.0.0
```

If using `main`:

```bash
git push origin main
git push origin v1.0.0
```

Or push all tags:

```bash
git push origin --tags
```

### 7. Verify Tag

```bash
git tag
```

Or check remote tags:

```bash
git ls-remote --tags origin
```

---

## 📦 Using a Release Tag in Yii2 App

After creating a release tag, update the Yii2 app `composer.json`.

```json
{
    "require": {
        "virgantara/unida-apps-support": "^1.0"
    }
}
```

Then run:

```bash
composer update virgantara/unida-apps-support -vvv
```

For a specific version:

```json
{
    "require": {
        "virgantara/unida-apps-support": "1.0.0"
    }
}
```

Then:

```bash
composer update virgantara/unida-apps-support -vvv
```

---

## 🔄 Updating Release Version

For bug fixes:

```bash
git tag -a v1.0.1 -m "Release v1.0.1"
git push origin v1.0.1
```

For new backward-compatible features:

```bash
git tag -a v1.1.0 -m "Release v1.1.0"
git push origin v1.1.0
```

For breaking changes:

```bash
git tag -a v2.0.0 -m "Release v2.0.0"
git push origin v2.0.0
```

---

## 🧹 Removing a Wrong Tag

Remove local tag:

```bash
git tag -d v1.0.0
```

Remove remote tag:

```bash
git push origin --delete v1.0.0
```

Create the corrected tag again:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

---

## 🧪 Testing Package Installation Locally

In a Yii2 application:

```bash
composer clear-cache
composer update virgantara/unida-apps-support -vvv
```

Check installed version:

```bash
composer show virgantara/unida-apps-support
```

---

## 📁 Suggested Package Structure

```text
Unida-Apps-Support/
├── composer.json
├── README.md
├── src/
│   └── components/
│       ├── AplikasiAuth.php
│       ├── OAuth2Client.php
│       ├── TokenManager.php
│       └── TokenService.php
└── LICENSE
```

---

## 🧾 Example Package `composer.json`

```json
{
    "name": "virgantara/unida-apps-support",
    "description": "Support components for Yii2 UNIDA Gontor applications",
    "type": "yii2-extension",
    "license": "MIT",
    "authors": [
        {
            "name": "Oddy Virgantara Putra",
            "email": "your-email@example.com"
        }
    ],
    "require": {
        "php": ">=7.4",
        "yiisoft/yii2": "~2.0.0",
        "yiisoft/yii2-httpclient": "*",
        "firebase/php-jwt": "^6.0"
    },
    "autoload": {
        "psr-4": {
            "virgantara\\": "src/"
        }
    }
}
```

If your component files are placed directly under:

```text
components/
```

then use:

```json
{
    "autoload": {
        "psr-4": {
            "virgantara\\": ""
        }
    }
}
```

---

## ✅ Recommended Release Checklist

Before creating a new tag:

- [ ] Code is committed
- [ ] `composer.json` is valid
- [ ] Namespace matches PSR-4 autoload
- [ ] README is updated
- [ ] No token is exposed through URL
- [ ] OAuth callback uses authorization code
- [ ] App jump uses target app `/site/start-sso`
- [ ] Version tag is created
- [ ] Tag is pushed to GitHub
- [ ] Yii2 app can install the tagged version

---

## 📄 License

This package is open-sourced under the MIT license.