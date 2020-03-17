<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyMinions\minions;

use DaPigGuy\PiggyMinions\inventory\MinionInventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\UUID;

class MinionInformation
{
    /** @var UUID */
    public $owner;

    /** @var MinionInventory */
    public $inventory;

    /** @var MinionType */
    public $type;
    /** @var int */
    public $level;
    /** @var int */
    public $resourcesCollected;
    /** @var int */
    public $lastSaved;

    public function __construct(UUID $owner, MinionInventory $inventory, MinionType $type, int $level, int $resourcesCollected, int $lastSaved)
    {
        $this->owner = $owner;
        $this->inventory = $inventory;
        $this->type = $type;
        $this->level = $level;
        $this->resourcesCollected = $resourcesCollected;
        $this->lastSaved = $lastSaved;
    }

    /**
     * @return UUID
     */
    public function getOwner(): UUID
    {
        return $this->owner;
    }

    public function getInventory(): MinionInventory
    {
        return $this->inventory;
    }

    public function getType(): MinionType
    {
        return $this->type;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getResourcesCollected(): int
    {
        return $this->resourcesCollected;
    }

    public function setResourcesCollected(int $resourcesCollected): void
    {
        $this->resourcesCollected = $resourcesCollected;
    }

    public function incrementResourcesCollected(int $amount): void
    {
        $this->resourcesCollected += $amount;
    }

    /**
     * @return int
     */
    public function getLastSaved(): int
    {
        return $this->lastSaved;
    }

    /**
     * @param int $lastSaved
     */
    public function setLastSaved(int $lastSaved): void
    {
        $this->lastSaved = $lastSaved;
    }

    public function toNBT(): CompoundTag
    {
        return new CompoundTag("MinionInformation", [
            new StringTag("OwnerUUID", $this->owner->toString()),
            new ListTag("MinionInventory", array_map(function (Item $item): CompoundTag {
                return $item->nbtSerialize();
            }, $this->inventory->getContents())),
            $this->type->toNBT(),
            new IntTag("MinionLevel", $this->level),
            new IntTag("ResourcesCollected", $this->resourcesCollected),
            new IntTag("LastSaved", $this->lastSaved)
        ]);
    }

    public static function fromNBT(CompoundTag $tag): self
    {
        $inventory = new MinionInventory(array_map(function (CompoundTag $tag): Item {
            return Item::nbtDeserialize($tag);
        }, $tag->getListTag("MinionInventory")->getValue()));
        $type = MinionType::fromNBT($tag->getCompoundTag("MinionType"));
        return new self(UUID::fromString($tag->getString("OwnerUUID")), $inventory, $type, $tag->getInt("MinionLevel"), $tag->getInt("ResourcesCollected"), $tag->getInt("LastSaved"));
    }
}