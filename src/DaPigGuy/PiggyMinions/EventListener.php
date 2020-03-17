<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyMinions;

use DaPigGuy\PiggyMinions\inventory\MinionInventory;
use DaPigGuy\PiggyMinions\minions\MinionInformation;
use DaPigGuy\PiggyMinions\minions\MinionType;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class EventListener implements Listener
{
    /** @var PiggyMinions */
    private $plugin;

    public function __construct(PiggyMinions $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($block->getId() === BlockIds::MOB_HEAD_BLOCK) {
            $item = $event->getItem();
            if (($minionInformation = $item->getNamedTag()->getCompoundTag("MinionInformation")) !== null) {
                if (($minionType = $minionInformation->getCompoundTag("MinionType")) !== null) {
                    if (($actionType = $minionType->getTag("ActionType")) instanceof IntTag && ($targetId = $minionType->getTag("TargetID")) instanceof IntTag) {
                        $skin = $player->getSkin();

                        $nbt = Entity::createBaseNBT($block->add(0.5, 0, 0.5));
                        $nbt->setTag(new CompoundTag("Skin", [
                            new StringTag("Name", $skin->getSkinId()),
                            new ByteArrayTag("Data", $skin->getSkinData()),
                            new ByteArrayTag("CapeData", $skin->getCapeData()),
                            new StringTag("GeometryName", $skin->getGeometryName()),
                            new ByteArrayTag("GeometryData", $skin->getGeometryData())
                        ]));
                        $nbt->setTag((new MinionInformation($player->getUniqueId(), new MinionInventory(), new MinionType($actionType->getValue(), $targetId->getValue()), $minionInformation->getInt("MinionLevel", 1), $minionInformation->getInt("ResourcesCollected", 0), time()))->toNBT());

                        $entity = Entity::createEntity("MinionEntity", $player->getLevel(), $nbt);
                        $entity->spawnToAll();

                        $event->setCancelled();
                    }
                }
            }
        }
    }
}