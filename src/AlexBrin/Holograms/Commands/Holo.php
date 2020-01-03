<?php


namespace AlexBrin\Holograms\Commands;


use AlexBrin\Holograms\HologramManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

/**
 * Class Holo
 * @package AlexBrin\Holograms\Commands
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class Holo extends Command
{
    const PREFIX = "§7[§baHologram§7]§r ";

    public function __construct()
    {
        parent::__construct('holo', 'Hologram management', '/holo', ['hologram', 'holograms']);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(self::PREFIX . "§cOnly for players");
            return true;
        }

        if (!$sender->hasPermission('atp.management')) {
            $sender->sendMessage(self::PREFIX . " §eNot enough rights");
            return true;
        }

        if (count($args) < 2) {
            $sender->sendMessage($this->help());
            return true;
        }

        $action = mb_strtolower(array_shift($args));
        $name = mb_strtolower(array_shift($args));

        switch ($action) {

            case 'create':
            case 'new':
            case 'c':
                $text = implode(" ", $args);
                if (substr($text, 0, 1) === "") {
                    $sender->sendMessage(self::PREFIX . "§eA hologram can't be empty");
                    return true;
                }

                $text = str_replace(['//n', '\n', "\n"], "\n", $text);
                $text = explode("\n", $text);

                $pos = $sender->asPosition();
                $pos->y += 2;
                $result = HologramManager::getInstance()->createHologram($name, $pos, $text);
                if (is_null($result)) {
                    $sender->sendMessage(self::PREFIX . "§cHologram with name `$name` already exists");
                    return true;
                }

                $sender->sendMessage(self::PREFIX . "§aHologram has been created");
                return true;

            case 'remove':
            case 'rm':
                $levelName = array_shift($args);

                if (!is_null($levelName)) {
                    $level = Server::getInstance()->getLevelByName($levelName);
                    $cnt = HologramManager::getInstance()->removeHologram($name, $level->getId());
                } else {
                    $cnt = HologramManager::getInstance()->removeAllHologramsByName($name);
                }

                $sender->sendMessage(self::PREFIX . "§aRemoved §e$cnt §aHolograms");
                return true;

            default:
                $sender->sendMessage($this->help());
                return true;
        }
    }

    private function help(): string
    {
        $messages = [
            self::PREFIX . 'Help:',
            '§2/holo <create|new> <name> <text> §7- §c Creating new hologram',
            '§2/holo <remove|rm> <name> [levelName] §7- §c Remove hologram by name',
        ];

        return implode("\n", $messages);
    }
}