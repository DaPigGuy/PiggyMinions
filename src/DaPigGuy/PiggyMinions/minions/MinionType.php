<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyMinions\minions;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

class MinionType
{

    const MINING_MINION = 0;
    const FARMING_MINION = 1;
    const LUMBERJACK_MINION = 2;
    const SLAYING_MINION = 3;

    /** @var int */
    public $actionType;
    /** @var int */
    public $targetId;

    public function __construct(int $actionType, int $targetId)
    {
        $this->actionType = $actionType;
        $this->targetId = $targetId;
    }

    public function getActionType(): int
    {
        return $this->actionType;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function toNBT(): CompoundTag
    {
        return new CompoundTag("MinionType", [
            new IntTag("ActionType", $this->actionType),
            new IntTag("TargetID", $this->targetId)
        ]);
    }

    public static function fromNBT(CompoundTag $tag): self
    {
        return new self($tag->getInt("ActionType"), $tag->getInt("TargetID"));
    }
}