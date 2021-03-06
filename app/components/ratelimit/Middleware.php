<?php

namespace App\components\ratelimit;

use App\components\Config;
use App\components\http\Request;
use App\components\http\Response;
use App\facades\RateLimit;
use App\middlewares\AbstractMiddleware;

class Middleware extends AbstractMiddleware
{
    private $config = [];

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $this->config = array_merge(Config::get('throttle'), $this->parseOptions());

        list($metric, $period, $throttle) = $this->parseConfig($request);

        if (!RateLimit::pass($metric, $period, $throttle, $remaining)) {
            return Response::output('', 429)->header('X-RateLimit-Period', $period)
                ->header('X-RateLimit-Throttle', $throttle)
                ->header('X-RateLimit-Remaining', $remaining);
        }

        return $this->next();
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function parseConfig(Request $request)
    {
        if (is_callable($this->config['metric'])) {
            $metric = call_user_func_array($this->config['metric'], compact('request'));
        } else {
            $metric = $this->config['metric'];
        }

        return [$metric, $this->config['period'], $this->config['throttle']];
    }

    /**
     * @return array
     */
    protected function parseOptions()
    {
        if ($this->getOptions()) {
            list($period, $throttle) = explode(',' , $this->getOptions());
            return compact('period', 'throttle');
        }

        return [];
    }
}
