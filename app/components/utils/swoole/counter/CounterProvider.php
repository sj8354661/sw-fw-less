<?php

namespace App\components\utils\swoole\counter;

use App\components\core\AbstractProvider;
use App\components\core\AppProvider;

class CounterProvider extends AbstractProvider implements AppProvider
{
    public static function bootApp()
    {
        parent::bootApp();

        Counter::init();
    }
}
