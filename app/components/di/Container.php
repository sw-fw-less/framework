<?php

namespace App\components\di;

use App\components\traits\Singleton;
use App\facades\File;
use DI\ContainerBuilder;

class Container
{
    use Singleton;

    /** @var \DI\Container  */
    private $diContainer;

    /**
     * Container constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $containerBuilder = new ContainerBuilder();
        if (config('storage.switch')) {
            $containerBuilder->enableCompilation(File::path('runtime/compiled'));
        }
        $this->diContainer = $containerBuilder->build();
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->diContainer, $name], $arguments);
    }
}
