<?php

namespace virgantara\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class AplikasiAuth extends Component
{
    public $baseurl;

    /**
     * Retrieves a list of allowed applications formatted for display.
     *
     * @return array
     */
    public function getRenderedAllowedAppsList()
    {
        $listApps = [];

        if (Yii::$app->user->isGuest) {
            return $listApps;
        }

        try {
            $session = Yii::$app->session;

            if (!$session->has('access_token')) {
                return $listApps;
            }

            $accessToken = $session->get('access_token');

            $hasil = $this->getAllowedAplikasi($accessToken);

            if (!empty($hasil['apps']) && is_array($hasil['apps'])) {
                foreach ($hasil['apps'] as $item) {
                    if (empty($item['app_name']) || empty($item['app_url'])) {
                        continue;
                    }

                    $listApps[] = [
                        'template' => '<a target="_blank" rel="noopener noreferrer" href="{url}">{label}</a>',
                        'label' => $item['app_name'],
                        'url' => $item['app_url'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            Yii::error('Error fetching allowed applications: ' . $e->getMessage(), __METHOD__);
        }

        return $listApps;
    }

    /**
     * Ambil daftar aplikasi yang boleh diakses user.
     *
     * Catatan:
     * - Tidak mengirim access_token lewat URL aplikasi.
     * - Tidak mengirim refresh_token lewat URL aplikasi.
     * - URL tujuan adalah endpoint jump/start-sso aplikasi tujuan.
     *
     * @param string $accessToken
     * @return array
     */
    public function getAllowedAplikasi($accessToken)
    {
        try {
            $client = new Client([
                'baseUrl' => rtrim($this->baseurl, '/'),
            ]);

            $response = $client->get('/app/list', [], [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->send();

            if (!$response->isOk) {
                Yii::warning([
                    'message' => 'Failed to fetch allowed applications',
                    'status' => $response->statusCode,
                    'body' => $response->content,
                ], __METHOD__);

                return [
                    'apps' => [],
                ];
            }

            $items = $response->data;

            if (!is_array($items)) {
                Yii::warning('Invalid /app/list response format', __METHOD__);

                return [
                    'apps' => [],
                ];
            }

            $apps = [];

            foreach ($items as $it) {
                if (empty($it['app_id']) || empty($it['app_name'])) {
                    continue;
                }

                $appUrl = $this->resolveStartSsoUrl($it);

                if (empty($appUrl)) {
                    Yii::warning([
                        'message' => 'Application does not have valid start_sso_url, login_sso_url, base_url, app_url, or redirect_uri',
                        'app' => $it,
                    ], __METHOD__);

                    continue;
                }

                $apps[] = [
                    'app_id' => $it['app_id'],
                    'client_id' => $it['client_id'] ?? null,
                    'app_name' => $it['app_name'],
                    'app_url' => $appUrl,
                ];
            }

            return [
                'apps' => $apps,
            ];
        } catch (\Throwable $e) {
            Yii::error('Error getAllowedAplikasi: ' . $e->getMessage(), __METHOD__);

            return [
                'apps' => [],
            ];
        }
    }

    /**
     * Resolve URL tujuan untuk jump antar aplikasi.
     *
     * Prioritas:
     * 1. start_sso_url
     * 2. login_sso_url
     * 3. base_url + jump_callback
     * 4. app_url + jump_callback
     * 5. domain dari redirect_uri + jump_callback
     *
     * @param array $item
     * @return string|null
     */
    private function resolveStartSsoUrl($item)
    {
        if (!empty($item['start_sso_url'])) {
            return $item['start_sso_url'];
        }

        if (!empty($item['login_sso_url'])) {
            return $item['login_sso_url'];
        }

        $jumpCallback = $item['jump_callback'] ?? '/site/start-sso';
        $jumpCallback = '/' . ltrim($jumpCallback, '/');

        if (!empty($item['base_url'])) {
            return rtrim($item['base_url'], '/') . $jumpCallback;
        }

        if (!empty($item['app_url'])) {
            return rtrim($item['app_url'], '/') . $jumpCallback;
        }

        if (!empty($item['redirect_uri'])) {
            $parts = parse_url($item['redirect_uri']);

            if (!empty($parts['scheme']) && !empty($parts['host'])) {
                $port = !empty($parts['port']) ? ':' . $parts['port'] : '';

                return $parts['scheme'] . '://' . $parts['host'] . $port . $jumpCallback;
            }
        }

        return null;
    }
}