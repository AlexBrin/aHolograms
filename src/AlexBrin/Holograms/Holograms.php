<?php

namespace AlexBrin\Holograms;

use AlexBrin\Holograms\Commands\Holo;
use pocketmine\plugin\PluginBase;

/**
 * Class Holograms
 * @package AlexBrin\Holograms
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class Holograms extends PluginBase
{
    private static $instance;

    public function onEnable()
    {
        self::$instance =& $this;

        $this->getConfig();
        HologramManager::getInstance();

        $this->getServer()->getCommandMap()->register('holograms', new Holo(), 'holo');
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);
    }

    public function onDisable()
    {
        HologramManager::getInstance()->saveAll();
    }

    /**
     * @return Holograms
     */
    public static function getInstance(): Holograms
    {
        return self::$instance;
    }
}