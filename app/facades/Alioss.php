<?php

namespace App\facades;

use League\Flysystem\Filesystem;

/**
 * Class Alioss
 *
 * @method static string bucket()
 * @method static string endpoint()
 * @method static Filesystem prepare($bucket = null)
 * @package App\facades
 */
class Alioss extends AbstractFacade
{
    protected static function getAccessor()
    {
        return \App\components\storage\Alioss::create();
    }
}