<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyMinions;

use DaPigGuy\PiggyMinions\entities\MinionEntity;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;

class PiggyMinions extends PluginBase
{
    public function onEnable(): void
    {
        Entity::registerEntity(MinionEntity::class, true);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
}