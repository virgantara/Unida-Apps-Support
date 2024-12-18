<?php
namespace virgantara\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use yii\base\Component;

use yii\httpclient\Client;
use yii\web\UnauthorizedHttpException;
use Yii;
class OAuth2Client extends Component
{
    public $tokenValidationUrl;
    public $tokenRefreshUrl;
    public $client_id;
    public $client_secret;

    public function logoutToken($accessToken){
        $client = new Client();
        $oauth_baseurl = Yii::$app->params['oauth']['baseurl'];
        try {
            // Send a POST request to an external endpoint to invalidate the token
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($oauth_baseurl.'/auth/logout') // Replace with your actual endpoint
                ->setData(['access_token' => $accessToken])
                ->send();

            if ($response->isOk) {
                Yii::info('Access token invalidated successfully.');
                // Remove the access token from the session
                
                return true;
                
            } else {
                Yii::error('Failed to invalidate access token. Response: ' . $response->content);
                return false;
            }
        } catch (\Exception $e) {
            Yii::error('Error sending POST request: ' . $e->getMessage());
            return false;
        }
    }

    public function validateAccessToken($accessToken)
    {
        try {
            // $client = new Client();
            $client = new Client(['baseUrl' => $this->tokenValidationUrl]);
            $response = $client->get('/auth/validate',[],[
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->send();
            
            if($response->headers['http-code'] != 200){
                throw new UnauthorizedHttpException('Invalid or expired access token.');
                // Yii::$app->session->setFlash('danger', Yii::t('app', $response->data['error_description']));
            }

            else{
                if ($response->isOk) {
                    $data = $response->data;
                    if (isset($data['valid']) && $data['valid'] === true) {
                        return $data['user']; // Return user information if needed
                    }
                } else {
                    throw new UnauthorizedHttpException('Invalid or expired access token.');
                }    
            }

            throw new UnauthorizedHttpException('Invalid or expired access token.');
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('Token validation failed: ' . $e->getMessage());
        }
    }

    public function refreshAccessToken($refreshToken)
    {
        try {
            $client = new Client(['baseUrl' => $this->tokenRefreshUrl]);

            $response = $client->post('/auth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $refreshToken,
            ])->send();

            if (!$response->isOk) {
                throw new UnauthorizedHttpException('Failed to refresh access token.');
            }

            $data = $response->data;

            $jwtSecretKey = Yii::$app->params['jwt_key'];
            $decoded = JWT::decode($data, new Key($jwtSecretKey, 'HS256'));
            if (isset($decoded->accessToken) && isset($decoded->refreshToken)) {
                
                return [
                    'access_token' => $decoded->accessToken,
                    'refresh_token' => $decoded->refreshToken,
                    'expires_in' => $decoded->accessTokenExpiresAt,
                ];
            }

            throw new UnauthorizedHttpException('Invalid response from token refresh endpoint.');
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('Token refresh failed: ' . $e->getMessage());
        }
    }
}