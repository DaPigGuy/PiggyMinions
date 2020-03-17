<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyMinions\inventory;

use pocketmine\inventory\BaseInventory;

class MinionInventory extends BaseInventory
{
    public function getName(): string
    {
        return "Minion Inventory";
    }

    public function getDefaultSize(): int
    {
        return 15;
    }
}