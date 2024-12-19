<?php

namespace virgantara\components;

use Yii;

use yii\base\Component;
use app\models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use yii\web\BadRequestHttpException;

class TokenService extends Component
{
    public function handleAuthCallback($accessToken, $refreshToken)
    {
        $result = Yii::$app->tokenManager->validateTokenFromOtherApps($accessToken);
        $token = $result['token'];

        $this->loginUser(
            $token['user']['uuid'],
            $accessToken,
            $refreshToken,
            $token['accessTokenExpiresAt']
        );
    }

    public function handleCallback($receivedJwt, $authCode)
    {
        $secretKey = Yii::$app->params['jwt_key'];
        $decoded = JWT::decode($receivedJwt, new Key($secretKey, 'HS256'));

        if ($decoded->iss !== Yii::$app->params['oauth']['redirectUri']) {
            throw new BadRequestHttpException('Invalid issuer.');
        }

        if ($decoded->exp < time()) {
            throw new BadRequestHttpException('Token has expired.');
        }

        $accessToken = Yii::$app->tokenManager->fetchAccessTokenWithAuthCode($authCode);
        $decodedToken = JWT::decode($accessToken, new Key($secretKey, 'HS256'));

        $this->loginUser(
            $decodedToken->uuid,
            $decodedToken->accessToken,
            $decodedToken->refreshToken ?? null,
            $decodedToken->accessTokenExpiresAt
        );
    }

    protected function loginUser($uuid, $accessToken, $refreshToken, $expiresIn)
    {
        $user = User::findOne(['uuid' => $uuid]);

        if (!$user) {
            throw new \Exception("User with $uuid not found");
        }

        Yii::$app->user->login($user);
        Yii::$app->session->set('access_token', $accessToken);
        Yii::$app->session->set('refresh_token', $refreshToken ?? null);
        Yii::$app->session->set('expires_in', $expiresIn);

    }
}
