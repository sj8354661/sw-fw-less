<?php

namespace App\components\auth\jwt;

interface UserProviderContract
{
    public function retrieveByToken($jwtToken, $signKey, $jid);
}
