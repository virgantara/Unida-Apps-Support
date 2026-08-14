<?php

namespace virgantara\components;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;
use Firebase\JWT\JWT;
use yii\base\InvalidConfigException;

class ApiManager extends Component
{
	public $api_baseurl;
	public $unida_app_client_id;
	public $unida_app_client_secret;
	public $timeout=30;
	public $unida_app_jwt_secret_key;
	public $token_ttl = 3600;
	private $_generatedToken;

	public function init()
    {
        parent::init();

        if (empty($this->api_baseurl)) {
            throw new InvalidConfigException(
                'ApiManager::$api_baseurl wajib diisi.'
            );
        }
    }

    public function generateToken()
    {
        if (empty($this->unida_app_client_id)) {
            throw new InvalidConfigException(
                'ApiManager::$unida_app_client_id wajib diisi.'
            );
        }

        if (empty($this->unida_app_client_secret)) {
            throw new InvalidConfigException(
                'ApiManager::$unida_app_client_secret wajib diisi.'
            );
        }

        if (empty($this->unida_app_jwt_secret_key)) {
            throw new InvalidConfigException(
                'ApiManager::$unida_app_jwt_secret_key wajib diisi.'
            );
        }

        $issuedAt = time();

        $payload = [
            'client_id' => $this->unida_app_client_id,
            'client_secret' => $this->unida_app_client_secret,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + (int) $this->token_ttl,
        ];

        return JWT::encode(
            $payload,
            $this->unida_app_jwt_secret_key,
            'HS256'
        );
    }

    protected function getAccessToken()
    {

        if ($this->_generatedToken === null) {
            $this->_generatedToken = $this->generateToken();
        }

        return $this->_generatedToken;
    }

    protected function createHttpClient()
    {
        return new Client([
            'baseUrl' => rtrim($this->api_baseurl, '/'),
            'requestConfig' => [
                'options' => [
                    CURLOPT_TIMEOUT => (int) $this->timeout,
                    CURLOPT_CONNECTTIMEOUT => 10,
                ],
            ],
        ]);
    }

    protected function getHeaders()
    {
        return [
            'x-access-token' => $this->getAccessToken(),
            'Accept' => 'application/json',
        ];
    }
    
	public function post($endpoint, $dataPost = [])
    {
        $response = $this->createHttpClient()
            ->post(
                $endpoint,
                $dataPost,
                $this->getHeaders()
            )
            ->send();

        if (!$response->isOk) {
            Yii::error([
                'message' => 'API POST request failed',
                'endpoint' => $endpoint,
                'statusCode' => $response->statusCode,
                'response' => $response->data,
            ], __METHOD__);

            return [];
        }

        return $response->data;
    }

    public function get($endpoint, $dataQuery = [])
    {
        $response = $this->createHttpClient()
            ->get(
                $endpoint,
                $dataQuery,
                $this->getHeaders()
            )
            ->send();

        if (!$response->isOk) {
            Yii::error([
                'message' => 'API GET request failed',
                'endpoint' => $endpoint,
                'statusCode' => $response->statusCode,
                'response' => $response->data,
            ], __METHOD__);

            return [];
        }

        return $response->data;
    }

    public function put($endpoint, $dataPut = [])
    {
        $response = $this->createHttpClient()
            ->put(
                $endpoint,
                $dataPut,
                $this->getHeaders()
            )
            ->send();

        if (!$response->isOk) {
            Yii::error([
                'message' => 'API PUT request failed',
                'endpoint' => $endpoint,
                'statusCode' => $response->statusCode,
                'response' => $response->data,
            ], __METHOD__);

            return [];
        }

        return $response->data;
    }

    public function delete($endpoint, $dataDelete = [])
    {
        $response = $this->createHttpClient()
            ->delete(
                $endpoint,
                $dataDelete,
                $this->getHeaders()
            )
            ->send();

        if (!$response->isOk) {
            Yii::error([
                'message' => 'API DELETE request failed',
                'endpoint' => $endpoint,
                'statusCode' => $response->statusCode,
                'response' => $response->data,
            ], __METHOD__);

            return [];
        }

        return $response->data;
    }
}