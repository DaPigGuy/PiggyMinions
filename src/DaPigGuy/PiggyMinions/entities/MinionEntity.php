<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyMinions\entities;

use DaPigGuy\PiggyMinions\minions\MinionInformation;
use DaPigGuy\PiggyMinions\minions\MinionType;
use DaPigGuy\PiggyMinions\utils\Utils;
use muqsit\invmenu\InvMenu;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\Crops;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class MinionEntity extends Human
{
    const ACTION_IDLE = 0;
    const ACTION_TURNING = 1;
    const ACTION_WORKING = 2;

    /** @var MinionInformation */
    public $minionInformation;

    /** @var int */
    public $currentAction = self::ACTION_IDLE;
    /** @var int */
    public $currentActionTicks = 0;

    /** @var Block|Entity */
    public $target;

    public function __construct(Level $level, CompoundTag $nbt, ?MinionInformation $minionInformation = null)
    {
        $this->minionInformation = $minionInformation;
        parent::__construct($level, $nbt);
    }

    public function initEntity(): void
    {
        parent::initEntity();
        $this->setScale(0.5);
        $this->minionInformation = $this->minionInformation ?? MinionInformation::fromNBT($this->namedtag->getCompoundTag("MinionInformation"));

        switch ($this->minionInformation->getType()->getActionType()) { //TODO: Item tiers based off of level
            case MinionType::MINING_MINION:
                $block = BlockFactory::get($this->minionInformation->getType()->getTargetId());
                $tools = [
                    BlockToolType::TYPE_SHOVEL => ItemIds::WOODEN_SHOVEL,
                    BlockToolType::TYPE_PICKAXE => ItemIds::WOODEN_PICKAXE,
                    BlockToolType::TYPE_AXE => ItemIds::WOODEN_AXE,
                    BlockToolType::TYPE_SHEARS => ItemIds::SHEARS
                ];
                $this->inventory->setItemInHand(ItemFactory::get($tools[$block->getToolType()]));
                break;
            case MinionType::FARMING_MINION:
                $this->inventory->setItemInHand(ItemFactory::get(ItemIds::WOODEN_HOE));
                break;
            case MinionType::LUMBERJACK_MINION:
                $this->inventory->setItemInHand(ItemFactory::get(ItemIds::WOODEN_AXE));
                break;
            case MinionType::SLAYING_MINION:
                $this->inventory->setItemInHand(ItemFactory::get(ItemIds::WOODEN_SWORD));
                break;
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if (!$this->closed && !$this->isFlaggedForDespawn() && $this->minionInformation !== null) {
            if ($this->ticksLived % 60 === 0 && $this->minionInformation->getType()->getActionType() === MinionType::FARMING_MINION) {
                for ($x = -2; $x <= 2; $x++) { //TODO: Customizable
                    for ($z = -2; $z <= 2; $z++) {
                        $block = $this->level->getBlock($this->add($x, 0, $z));
                        if ($block instanceof Crops || $block->getId() === BlockIds::NETHER_WART_PLANT) {
                            $max = $block instanceof Crops ? 7 : 3;
                            $block->setDamage($block->getDamage() + 1 < $max ? $block->getDamage() + 1 : $max);
                            $this->level->setBlock($block, $block);
                        }
                    }
                }
            }
            if ($this->target === null) {
                $blocks = [];
                switch ($this->minionInformation->getType()->getActionType()) {
                    case MinionType::MINING_MINION:
                        for ($x = -2; $x <= 2; $x++) { //TODO: Customizable
                            for ($z = -2; $z <= 2; $z++) {
                                $block = $this->level->getBlock($this->add($x, -1, $z));
                                if ($block->getId() === BlockIds::AIR || $block->getId() === $this->minionInformation->getType()->getTargetId()) $blocks[] = $block;
                            }
                        }
                        if (count($blocks) > 0) $this->target = $blocks[array_rand($blocks)];
                        break;
                    case MinionType::FARMING_MINION:
                        for ($x = -2; $x <= 2; $x++) { //TODO: Customizable
                            for ($z = -2; $z <= 2; $z++) {
                                $farmland = $this->level->getBlock($this->add($x, -1, $z));
                                $block = $this->level->getBlock($this->add($x, 0, $z));
                                if (
                                    (in_array($farmland->getId(), [BlockIds::GRASS, BlockIds::DIRT, BlockIds::FARMLAND, BlockIds::SOUL_SAND]) &&
                                        ($this->minionInformation->getType()->getTargetId() !== BlockIds::NETHER_WART_PLANT || $farmland->getId() === BlockIds::SOUL_SAND)
                                    ) && ($block->getId() === BlockIds::AIR || ($block->getId() === $this->minionInformation->getType()->getTargetId() && $block->getDamage() >= ($block instanceof Crops ? 7 : 3)))
                                ) $blocks[] = $block;
                            }
                        }
                        if (count($blocks) > 0) $this->target = $blocks[array_rand($blocks)];
                        break;
                }
            }
            $this->currentActionTicks++;
            if ($this->target instanceof Block) {
                $this->target = $this->level->getBlock($this->target);
                if (($this->target->getId() !== BlockIds::AIR && $this->target->getId() !== $this->minionInformation->getType()->getTargetId()) ||
                    (
                        $this->minionInformation->getType()->getActionType() === MinionType::FARMING_MINION && (
                            !in_array($this->target->getLevel()->getBlock($this->target->subtract(0, 1))->getId(), [BlockIds::GRASS, BlockIds::DIRT, BlockIds::FARMLAND, BlockIds::SOUL_SAND]) ||
                            ($this->minionInformation->getType()->getTargetId() !== BlockIds::NETHER_WART_PLANT && $this->target->getLevel()->getBlock($this->target->subtract(0, 1))->getId() === BlockIds::SOUL_SAND)
                        )
                    )) {
                    $this->currentAction = self::ACTION_IDLE;
                    $this->currentActionTicks = 59;
                    $this->target = null;
                }
            }
            switch ($this->currentAction) {
                case self::ACTION_IDLE:
                    if ($this->currentActionTicks >= 60 && $this->target !== null) { //TODO: Customize
                        $this->currentAction = self::ACTION_TURNING;
                        $this->currentActionTicks = 0;
                    }
                    break;
                case self::ACTION_TURNING:
                    $this->lookAt($this->target->multiply($this->currentActionTicks / 5));
                    if ($this->currentActionTicks === 5) {
                        $this->currentAction = self::ACTION_WORKING;
                        $this->currentActionTicks = 0;
                    }
                    break;
                case self::ACTION_WORKING:
                    if ($this->minionInformation->getType()->getActionType() === MinionType::MINING_MINION || $this->minionInformation->getType()->getActionType() === MinionType::FARMING_MINION) {
                        $isPlacing = $this->target->getId() === BlockIds::AIR;
                        if (!$isPlacing) {
                            if ($this->currentActionTicks === 1) $this->level->broadcastLevelEvent($this->target, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int)(65535 / 60));

                            $pk = new AnimatePacket();
                            $pk->action = AnimatePacket::ACTION_SWING_ARM;
                            $pk->entityRuntimeId = $this->getId();
                            $this->level->broadcastPacketToViewers($this, $pk);
                        }
                    }
                    if ($this->currentActionTicks === 60) { //TODO: Customizable
                        switch ($this->minionInformation->getType()->getActionType()) {
                            /** @noinspection PhpMissingBreakStatementInspection */
                            case MinionType::FARMING_MINION:
                                $this->level->setBlock($this->target->subtract(0, 1), BlockFactory::get($this->minionInformation->getType()->getTargetId() === BlockIds::NETHER_WART_PLANT ? BlockIds::SOUL_SAND : BlockIds::FARMLAND));
                            case MinionType::MINING_MINION:
                                $this->level->setBlock($this->target, BlockFactory::get($this->target->getId() === BlockIds::AIR ? $this->minionInformation->getType()->getTargetId() : BlockIds::AIR));
                                $drops = $this->target->getDropsForCompatibleTool(ItemFactory::get(ItemIds::AIR));
                                if (empty($drops)) $drops = $this->target->getSilkTouchDrops(ItemFactory::get(ItemIds::AIR));
                                $this->minionInformation->getInventory()->addItem(...$drops);
                                foreach ($drops as $drop) {
                                    $this->minionInformation->incrementResourcesCollected($drop->getCount());
                                }
                                break;
                        }
                        $this->currentAction = self::ACTION_IDLE;
                        $this->currentActionTicks = 0;
                        $this->target = null;
                    }
                    break;
            }
        }
        return $hasUpdate;
    }

    public function getName(): string
    {
        return "Minion";
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player && $damager->getUniqueId()->equals($this->minionInformation->getOwner())) {
                $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                $menu->setName("Minion " . Utils::getRomanNumeral($this->minionInformation->getLevel()));
                $menu->getInventory()->setContents(array_fill(0, 54, ItemFactory::get(ItemIds::INVISIBLE_BEDROCK)));
                $menu->getInventory()->setItem(48, ItemFactory::get(ItemIds::CHEST));
                $menu->getInventory()->setItem(53, ItemFactory::get(ItemIds::BEDROCK));
                foreach ($this->minionInformation->getInventory()->getContents(true) as $slot => $item) {
                    $menu->getInventory()->setItem((int)(21 + ($slot % 5) + (9 * floor($slot / 5))), $item);
                }
                $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
                    switch ($action->getSlot()) {
                        case 48:
                            foreach (array_reverse($this->minionInformation->getInventory()->getContents(), true) as $slot => $item) {
                                if ($player->getInventory()->canAddItem($item)) {
                                    $player->getInventory()->addItem($item);
                                    $this->minionInformation->getInventory()->setItem($slot, ItemFactory::get(ItemIds::AIR));
                                }
                            }
                            break;
                        case 53:
                            $player->removeWindow($action->getInventory());
                            $this->minionInformation->getInventory()->dropContents($this->level, $this);
                            $minionItem = Item::get(Item::MOB_HEAD, 3);
                            $minionItem->setNamedTagEntry($this->minionInformation->toNBT());
                            $this->level->dropItem($this, $minionItem);
                            $this->flagForDespawn();
                            break;
                        default:
                            for ($i = 0; $i <= 15; $i++) {
                                $slot = (int)(21 + ($i % 5) + (9 * floor($i / 5)));
                                if ($action->getSlot() === $slot) {
                                    $player->getInventory()->addItem($itemClicked);

                                    $remaining = $itemClicked->getCount();
                                    /** @var Item $item */
                                    foreach (array_reverse($this->minionInformation->getInventory()->all($itemClicked), true) as $slot => $item) {
                                        $itemCount = $item->getCount();
                                        $this->minionInformation->getInventory()->setItem($slot, $item->setCount($itemCount - $remaining > 0 ? $itemCount - $remaining : 0));
                                        $remaining -= $itemCount;

                                        if ($remaining === 0) break;
                                    }
                                    break;
                                }
                            }
                            break;
                    }
                    foreach ($this->minionInformation->getInventory()->getContents(true) as $slot => $item) {
                        $action->getInventory()->setItem((int)(21 + ($slot % 5) + (9 * floor($slot / 5))), $item);
                    }
                    return false;
                });
                $menu->send($damager);
            }
        }
    }

    public function move(float $dx, float $dy, float $dz): void
    {
        //NOOP
    }

    public function addEffect(EffectInstance $effect): bool
    {
        return false;
    }

    public function saveNBT(): void
    {
        parent::saveNBT();
        $this->minionInformation->setLastSaved(time());
        $this->namedtag->setTag($this->minionInformation->toNBT());
    }
}