<?php

namespace AlexBrin\Holograms\Objects;

use AlexBrin\Holograms\Holograms;
use JsonSerializable;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

/**
 * Class Hologram
 * @package AlexBrin\Holograms\Objects
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class Hologram implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var Position
     */
    private $pos;

    /**
     * @var float
     */
    private $sourceY;

    /**
     * @var string[]
     */
    private $text = [];

    /**
     * @var int
     */
    private $textCount = 0;

    /**
     * @var int[]
     */
    private $entityIds = [];

    /**
     * Hologram constructor.
     *
     * @param string   $name
     * @param Position $pos
     * @param string[] $text
     */
    public function __construct(string $name, Position $pos, array $text = [])
    {
        $this->name = $name;
        $this->pos = $pos;
        $this->text = $text;

        $this->sourceY = $pos->y;

        $this->textCount = count($text);
        $this->recalculateY();

        for ($i = 0; $i < $this->textCount; $i++) {
            $this->entityIds[] = Entity::$entityCount++;
        }
    }

    public function getLevelName(): string {
        return $this->pos->getLevel()->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getText(): array
    {
        return $this->text;
    }

    public function setText(array $text)
    {
        $this->text = $text;
    }

    public function getLineOffset(): float
    {
        return Holograms::getInstance()->getConfig()->get('lineOffset', 0.35);
    }

    protected function recalculateY()
    {
        $newTextCount = count($this->text);
        if($this->textCount < $newTextCount) {
            for($i = 0; $i < ($newTextCount - $this->textCount); $i++) {
                $this->entityIds[] = Entity::$entityCount++;
            }
        }

        $this->textCount = $newTextCount;
        $this->pos->y = $this->sourceY;
        $this->pos->y += $this->getLineOffset() * $this->textCount;
    }

    /**
     * @param bool $send
     *
     * @return AddPlayerPacket[]|null
     */
    public function create(bool $send = true): ?array
    {
        $this->recalculateY();
        $packets = [];
        for ($i = 0; $i < $this->textCount; $i++) {
            $packets[] = $this->createAddPacket($this->entityIds[$i], $this->text[$i]);
        }

        if ($send) {
            $this->broadcast($packets);
            return null;
        }

        return $packets;
    }

    /**
     * @param bool $send
     *
     * @return RemoveActorPacket|null
     */
    public function destroy(bool $send = true): ?array
    {
        $packets = [];
        foreach ($this->entityIds as $entityId) {
            $packets[] = $this->createRemovePacket($entityId);
        }

        if ($send) {
            $this->broadcast($packets);
            return null;
        }

        return $packets;
    }

    public function update()
    {
        $this->recalculateY();
        $this->broadcast(array_merge($this->destroy(false), $this->create(false));
    }

    /**
     * @param DataPacket[] $packets
     */
    protected function broadcast(array $packets)
    {
        $batch = new BatchPacket();
        foreach($packets as $packet) {
            $batch->addPacket($packet);
        }

        foreach($this->pos->getLevel()->getPlayers() as $player) {
            $player->sendDataPacket($batch);
        }
    }

    /**
     * @param int    $entityId
     * @param string $text
     *
     * @return AddPlayerPacket
     */
    final protected function createAddPacket(int $entityId, string $text): AddPlayerPacket
    {
        $pk = new AddPlayerPacket;
        $pk->uuid = UUID::fromRandom();
        $pk->entityRuntimeId = $entityId;
        $pk->username = $text;
        $pk->position = clone $this->pos;
        $pk->item = ItemFactory::get(Item::AIR, 0, 0);
        $pk->metadata = [
            Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE],
            Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
        ];

        $this->pos->y -= $this->getLineOffset();

        return $pk;
    }

    /**
     * @param int $entityId
     *
     * @return RemoveActorPacket
     */
    final protected function createRemovePacket(int $entityId): RemoveActorPacket
    {
        $pk = new RemoveActorPacket;
        $pk->entityUniqueId = $entityId;

        return $pk;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return [
            'name' => $this->name,
            'text' => $this->text,
            'position' => [
                'level' => $this->getLevelName(),
                'x' => $this->pos->x,
                'y' => $this->sourceY,
                'z' => $this->pos->z,
            ]
        ];
    }

    /**
     * @param array $data
     * - @var string name
     * - @var array position
     * - - @var string level
     * - - @var float x
     * - - @var float y
     * - - @var float z
     * - @var string[] text
     *
     * @return Hologram|null
     *
     */
    public static function fromArray(array $data): ?Hologram
    {
        $position = new Position(
            $data['position']['x'],
            $data['position']['y'],
            $data['position']['z'],
            Server::getInstance()->getLevelByName($data['position']['level'])
        );

        return new Hologram($data['name'], $position, $data['text']);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
