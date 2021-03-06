<?php

namespace App\middlewares;

use App\components\Config;
use App\components\http\Request;

class Cors extends AbstractMiddleware
{
    /**
     * @param Request $request
     * @return \App\components\http\Response|mixed
     */
    public function handle(Request $request)
    {
        if (Config::get('cors.switch')) {
            return $this->next()->header('Access-Control-Allow-Origin', (string)Config::get('cors.origin'));
        }

        return $this->next();
    }
}
