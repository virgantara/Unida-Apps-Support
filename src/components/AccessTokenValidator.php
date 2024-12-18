<?php

namespace virgantara\components;

use Yii;
use yii\web\UnauthorizedHttpException;

class AccessTokenValidator
{
    /**
     * Validates the access token from the request headers.
     * 
     * @return bool
     * @throws UnauthorizedHttpException if the token is missing or invalid.
     */
    public static function validate()
    {
        $hasil = false;
        try {
            
            if (Yii::$app->tokenManager->validateOrRefreshToken()) {
                $hasil = true; 
            }
        } catch (\Exception $e) {
            $hasil = false;
        }
        
        return $hasil;
        
    }
}
