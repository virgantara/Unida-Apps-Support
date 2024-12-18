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


### 1. 🛠️ **TokenManager**

A component for securely generating, validating, and managing tokens.

**Features**:
- Generate secure tokens.
- Validate tokens with expiration handling.
- Token encryption and decryption support.

**Example Usage**:

```php
use unidagontor\components\TokenManager;

$tokenManager = new TokenManager();
$token = $tokenManager->generateToken(['user_id' => 123]);

if ($tokenManager->validateToken($token)) {
    echo "Token is valid!";
}
```

### 2. 🔐 **Oauth2Client**
```php
use unidagontor\components\Oauth2Client;

$oauthClient = new Oauth2Client([
    'clientId' => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri' => 'https://your-app.com/callback',
    'provider' => 'google',
]);

$authUrl = $oauthClient->getAuthorizationUrl();
echo "Login with Google: <a href='{$authUrl}'>Login</a>";
```

### 3. 🔑 **AplikasiAuth**
```php
use unidagontor\components\AplikasiAuth;

$auth = new AplikasiAuth();
if ($auth->login('username', 'password')) {
    echo "Login successful!";
} else {
    echo "Invalid credentials!";
}
```


### 4. 🚀 **Installation**
```bash
composer require unidagontor/apps-support-components
```