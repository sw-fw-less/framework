<?php

namespace SwFwLess\facades;

/**
 * Class Math
 *
 * @method static writeFile($filepath, $content, $native = false)
 * @method static readFile($filepath, $native = false)
 * @method static appendFile($filepath, $content, $native = false)
 *
 * @package SwFwLess\facades
 */
class MemoryMap extends AbstractFacade
{
    /**
     * @return \SwFwLess\components\traits\Singleton|\SwFwLess\components\storage\file\mmap\MemoryMap|null
     * @throws \Exception
     */
    protected static function getAccessor()
    {
        return \SwFwLess\components\storage\file\mmap\MemoryMap::create([]);
    }
}