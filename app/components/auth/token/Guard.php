<?php

namespace App\components\auth\token;

use App\components\auth\GuardContract;
use App\components\http\Request;

class Guard implements GuardContract
{
    /**
     * @param Request $credentialCarrier
     * @param $tokenKey
     * @param UserProviderContract $userProvider
     * @param $config
     * @return bool
     */
    public function validate($credentialCarrier, $tokenKey, $userProvider, $config)
    {
        $token = $credentialCarrier->get($tokenKey) ?: $credentialCarrier->header(strtolower($tokenKey));
        return (bool)$userProvider->retrieveByToken($token);
    }
}
