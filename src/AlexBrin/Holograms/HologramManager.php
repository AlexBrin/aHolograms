<?php

namespace AlexBrin\Holograms;

use AlexBrin\Holograms\Exceptions\CloneNotSupportedException;
use AlexBrin\Holograms\Objects\Hologram;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\Player;
use pocketmine\utils\Config;

/**
 * Class HologramManager
 * @package AlexBrin\Holograms
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class HologramManager
{

    /**
     * @var Hologram[][]
     */
    private $holograms = [];

    private static $instance;

    private function __construct()
    {
        $f = Holograms::getInstance()->getDataFolder();
        $logger = Holograms::getInstance()->getLogger();

        $logger->notice("Loading holograms...");
        $worlds = 0;
        $hologramCount = 0;
        $hologramList = (new Config($f . 'hologramList.json', Config::JSON))->getAll();
        foreach ($hologramList as $level => $holograms) {
            $this->holograms[$level] = [];
            $worlds++;
            foreach ($holograms as $hologramName => $hologram) {
                $this->holograms[$level][$hologramName] = Hologram::fromArray($hologram);
                $hologramCount++;
            }
        }

        $logger->notice(sprintf("Loaded %s holograms in %s worlds", $hologramCount, $worlds));
    }

    public function saveAll()
    {
        $logger = Holograms::getInstance()->getLogger();
        $logger->notice("Saving holograms...");
        $cfg = new Config(Holograms::getInstance()->getDataFolder() . 'hologramList.json', Config::JSON);
        $cfg->setAll($this->holograms);
        $cfg->save();
        $logger->notice("Holograms has been saved");
    }

    /**
     * @throws CloneNotSupportedException
     */
    public function __clone()
    {
        throw new CloneNotSupportedException();
    }

    /**
     * @param Hologram $hologram
     *
     * @return bool
     */
    public function addHologram(Hologram &$hologram): bool
    {
        if (!isset($this->holograms[$hologram->getLevelName()])) {
            $this->holograms[$hologram->getLevelName()] = [];
        } elseif (isset($this->holograms[$hologram->getLevelName()][$hologram->getName()])) {
            return false;
        }

        $this->holograms[$hologram->getLevelName()][$hologram->getName()] = $hologram;
        return true;
    }

    /**
     * @param string   $name
     * @param string|null $level
     *
     * @return Hologram|null
     */
    public function getHologram(string $name, ?string $level = null): ?Hologram
    {
        if (is_null($level) && !isset($this->holograms[$level])) {
            return $this->findHologramByName($name)[0] ?? null;
        }

        return $this->holograms[$level][$name] ?? null;
    }

    /**
     * @param string $level
     *
     * @return Hologram[]
     */
    public function getHologramsInLevel(string $level): array
    {
        return $this->holograms[$level] ?? [];
    }

    /**
     * @param string $name
     *
     * @return Hologram[]
     */
    public function findHologramByName(string $name): array
    {
        $holograms = [];

        foreach ($this->holograms as $hologramList) {
            if (isset($hologramList[$name])) {
                $holograms[] = $hologramList[$name];
            }
        }

        return $holograms;
    }

    /**
     * @param string   $name
     * @param string|null $level
     *
     * @return int
     */
    public function removeHologram(string $name, ?string $level): int
    {
        if (is_null($level) || !isset($this->holograms[$level])) {
            return $this->removeAllHologramsByName($name);
        }

        $this->holograms[$level][$name]->destroy();
        unset($this->holograms[$level][$name]);
        return 1;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function removeAllHologramsByName(string $name): int
    {
        $i = 0;
        foreach ($this->holograms as $level => $hologramList) {
            if (isset($hologramList[$name])) {
                $this->holograms[$level][$name]->destroy();
                unset($this->holograms[$level][$name]);
                $i++;
            }
        }

        return $i;
    }

    /**
     * @param Player $player
     */
    public function sendToPlayer(Player $player)
    {
        $pk = new BatchPacket();

        foreach ($this->getHologramsInLevel($player->getLevel()->getName()) as $hologram) {
            foreach ($hologram->create(false) as $packet) {
                $pk->addPacket($packet);
            }
        }

        $player->sendDataPacket($pk);
    }

    /**
     * @param string   $name
     * @param Position $pos
     * @param string[] $text
     *
     * @param bool     $create
     *
     * @param bool     $addToManager
     *
     * @return Hologram|null
     */
    public function createHologram(string $name, Position $pos, array $text, bool $create = true, bool $addToManager = true): ?Hologram
    {
        $hologram = (new Hologram($name, $pos, $text));
        if($addToManager && !$this->addHologram($hologram)) {
            return null;
        }

        if ($create) {
            $hologram->create();
        }

        return $hologram;
    }

    /**
     * @return HologramManager
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}