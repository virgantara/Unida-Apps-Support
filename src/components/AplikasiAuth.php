<?php
namespace unidagontor\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use yii\base\Component;

use yii\helpers\Url;
use yii\httpclient\Client;
use yii\web\UnauthorizedHttpException;
use Yii;
class AplikasiAuth extends Component
{
    public $baseurl;
    

    public function getAllowedAplikasi($accessToken)
    {
        try {
            // $client = new Client();
            $client = new Client(['baseUrl' => $this->baseurl]);
            $response = $client->get('/app/list',[],[
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->send();
            
            if($response->headers['http-code'] != 200){
                return [
                    'token' => '',
                    'apps' => []
                ];
                // throw new UnauthorizedHttpException('Invalid or expired access token.');
                // Yii::$app->session->setFlash('danger', Yii::t('app', $response->data['error_description']));
            }

            else{
                if ($response->isOk) {
                    $items = $response->data;

                    $tmp = [];
                    $email = '';
                    $uuid = '';
                    $display_name = '';

                    foreach($items as $it) {
                        $email = $it['email'];
                        $uuid = $it['uuid'];
                        $display_name = $it['nama_user'];

                        $url = $it['success_callback'];
                        $query = parse_url($url, PHP_URL_QUERY);

                        if ($query) {
                            $url .= '&token=';
                        } else {
                            $url .= '?token=';
                        }
                        $tmp[] = [
                            'app_id' => $it['app_id'],
                            'app_name' => $it['app_name'],
                            'app_url' => $url
                        ];
                    }

                    $token_payload = [
                      'iss' => Url::home(true),
                      'sub' => $email,
                      'uuid' => $uuid,
                      'name' => $display_name,
                      'email' => $email,
                      'iat' => time(),
                      'exp' => time()+(60*30),
                      'apps' => $tmp

                    ];

                    $key = Yii::$app->params['jwt_key'];
                    $token = JWT::encode($token_payload, base64_decode(strtr($key, '-_', '+/')), 'HS256');
                    return [
                        'token' => $token,
                        'apps' => $tmp
                    ];
                } else {
                    return [
                        'token' => '',
                        'apps' => []
                    ];
                }    
            }

            return [
                'token' => '',
                'apps' => []
            ];
        } catch (\Exception $e) {
            return [
                'token' => '',
                'apps' => []
            ];
        }
    }

}