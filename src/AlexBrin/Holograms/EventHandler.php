<?php

namespace AlexBrin\Holograms;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;

/**
 * Class EventHandler
 * @package AlexBrin\Holograms
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class EventHandler implements Listener
{
    /**
     * @var int[]
     */
    private $loadedPlayers = [];

    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $this->send($event->getPlayer());
    }

    public function onPlayerRespawn(PlayerRespawnEvent $event)
    {
        $this->send($event->getPlayer());
    }

    public function onPlayerQuit(PlayerQuitEvent $event) {
        $playerName = $event->getPlayer()->getName();
        if(isset($this->loadedPlayers[$playerName])) {
            unset($this->loadedPlayers[$playerName]);
        }
    }

    private function send(Player $player)
    {
        $playerName = $player->getName();
        if (isset($this->loadedPlayers[$playerName])) {
            if ($this->loadedPlayers[$playerName] === $player->getLevel()->getName()) {
                return;
            }
        }

        $this->loadedPlayers[$playerName] = $player->getLevel()->getName();
        HologramManager::getInstance()->sendToPlayer($player);
    }
}