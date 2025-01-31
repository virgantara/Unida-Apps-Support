<?php

namespace virgantara\components;

use Yii;
use yii\base\Component;
use yii\web\UnauthorizedHttpException;
use yii\base\InvalidConfigException;

class TokenManager extends Component
{
    /**
     * Validates an access token from other applications.
     *
     * @param string $access_token The access token to validate.
     * @return array|false The response from the validation endpoint, or false on failure.
     * @throws InvalidConfigException if the OAuth base URL is missing.
     */
    public function validateTokenFromOtherApps($access_token)
    {
        
        $baseUrl = Yii::$app->params['oauth']['baseurl'] ?? null;

        if (!$baseUrl) {
            throw new InvalidConfigException('Missing "baseurl" in Yii::$app->params[\'oauth\']');
        }

        $validateUrl = rtrim($baseUrl, '/') . '/oauth/validate';

        // Initialize cURL request
        $ch = curl_init($validateUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            Yii::error('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Decode the response
        $response = json_decode($response, true);

        if ($httpCode !== 200 || !$response || isset($response['error'])) {
            Yii::error('Token validation failed: ' . ($response['error_description'] ?? 'Unknown error'));
            return $response;
        }

        return $response;
    }

    /**
     * Fetches an access token using an authorization code.
     *
     * @param string $code Authorization code received from the OAuth provider.
     * @return array|null The access token response or null if an error occurs.
     * @throws InvalidConfigException if required OAuth parameters are missing.
     */
    public function fetchAccessTokenWithAuthCode($code)
    {
        $oauthParams = Yii::$app->params['oauth'] ?? [];

        $clientId = $oauthParams['client_id'] ?? null;
        $clientSecret = $oauthParams['client_secret'] ?? null;
        $baseUrl = $oauthParams['baseurl'] ?? null;
        $redirectUri = $oauthParams['redirectUri'] ?? null;

        // Exchange the authorization code for an access token
        $data = http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
        ]);

        $tokenUrl = rtrim($baseUrl, '/') . '/oauth/token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            Yii::error('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        } 

        curl_close($ch);

        $accessToken = json_decode($response, true);

        if (isset($accessToken['error'])) {
            Yii::error('OAuth Error: ' . $accessToken['error_description'] ?? 'Unknown error');
            return null;
        }

        return $accessToken;
    }

    /**
     * Validates the access token or attempts to refresh it if expired.
     * Returns true if successful, false otherwise.
     */
    public function validateOrRefreshToken()
    {
        $session = Yii::$app->session;
        $accessToken = $session->get('access_token');
        $refreshToken = $session->get('refresh_token');

        try {
            if ($accessToken) {
                Yii::$app->oauth2->validateAccessToken($accessToken);
                return true;
            }
        } catch (UnauthorizedHttpException $e) {
            return $this->refreshToken($refreshToken);
        }

        return false;
    }

    /**
     * Attempts to refresh the access token. Returns true if successful.
     */
    protected function refreshToken($refreshToken)
    {
        if (!$refreshToken) {
            $this->handleTokenFailure();
            return false;
        }

        try {
            $result = Yii::$app->oauth2->refreshAccessToken($refreshToken);
            $session = Yii::$app->session;
            $session->set('access_token', $result['access_token']);
            $session->set('refresh_token', $result['refresh_token'] ?? null);
            $session->set('expires_in', $result['expires_in']);
            return true;
        } catch (UnauthorizedHttpException $refreshError) {
            $this->handleTokenFailure();
            return false;
        }
    }

    /**
     * Handles token failure by logging out the user and clearing the session.
     */
    protected function handleTokenFailure()
    {
        Yii::$app->session->removeAll(['access_token', 'refresh_token']);

        if (!Yii::$app->user->isGuest) {
            $user = Yii::$app->user->identity;
            $user->access_token = null;
            $user->save(false, ['access_token']);
            Yii::$app->user->logout();
        }
    }
}
