<?php

namespace virgantara\components;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

class ApiManager extends Component
{
	public $api_baseurl;
	public $client_token;
    
	public function post($endpoint, $dataPost)
	{
		$client = new Client(['baseUrl' => $this->api_baseurl]);
	    $headers = ['x-access-token'=>$this->client_token];

	    $response = $client->post($endpoint, $dataPost, $headers)->send();
                            
        $results = [];
        if ($response->isOk) {
            $results = $response->data; 
        }

        return $results;
	}

	public function get($endpoint, $dataQuery)
	{
		$client = new Client(['baseUrl' => $this->api_baseurl]);
	    $headers = ['x-access-token'=>$this->client_token];

	    $response = $client->get($endpoint, $dataQuery, $headers)->send();
                            
        $results = [];
        if ($response->isOk) {
            $results = $response->data; 
        }

        return $results;
	}
}