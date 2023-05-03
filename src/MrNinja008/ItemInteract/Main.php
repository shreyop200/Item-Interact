<?php

declare(strict_types=1);

namespace MrNinja008\ItemInteract;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use function explode;

class Main extends PluginBase implements Listener {

    /** @var array */
    private $items = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->items = $this->getConfig()->getAll();
        unset($this->items['item-on-respawn']);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        foreach ($this->items as $itemName => $command) {
            if (strpos($itemName, 'Item') !== false) {
                $itemId = explode(':', $command);
                if (count($itemId) !== 2) {
                    $this->getLogger()->error("Invalid config for item $itemName. Use the format ID:META.");
                    continue;
                }
                $item = ItemFactory::getInstance()->get((int)$itemId[0], (int)$itemId[1], 1);
                $customName = $this->getConfig()->get($itemName . '-Name');
                if ($customName !== null) {
                    $item->setCustomName($customName);
                }
                $lore = $this->getConfig()->get($itemName . '-Lore');
                if ($lore !== null) {
                    $item->setLore(explode('\n', $lore));
                }
                $item->getNamedTag()->setString('item-interact-command', $this->getConfig()->get($itemName . '-Command'));
                $player->getInventory()->addItem($item);
            }
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void {
        $item = $event->getItem();
        if ($item->getNamedTag()->hasTag('item-interact-dropped')) {
            $event->getItem()->getNamedTag()->removeTag('item-interact-dropped');
        }
    }

    public function onClick(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if ($item->getNamedTag()->hasTag('item-interact-command')) {
            $command = $item->getNamedTag()->getString('item-interact-command');
            $this->getServer()->getCommandMap()->dispatch($player, $command);
            $event->setCancelled();
            if (!$item->getNamedTag()->hasTag('item-interact-dropped')) {
                $item->getNamedTag()->setByte('item-interact-dropped', 1);
                $player->getInventory()->setItemInHand($item);
            }
        }
    }

    public function onTransaction(InventoryTransactionEvent $event): void {
        $transaction = $event->getTransaction();
        foreach ($transaction->getActions() as $action) {
            $item = $action->getSourceItem();
            if ($item->getNamedTag()->hasTag('item-interact-command')) {
                $event->setCancelled();
                break;
            }
       
