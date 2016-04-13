<?php

namespace Muqsit;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerItemHeldEvent;

class Withdraw extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getLogger()->info("§8[§aWithdraw§8] §eFinding an economy plugin...");
		$pm = $this->getServer()->getPluginManager();
		if(!($this->money = $pm->getPlugin("PocketMoney")) && !($this->money = $pm->getPlugin("EconomyAPI")) && !($this->money = $pm->getPlugin("MassiveEconomy")) && !($this->money = $pm->getPlugin("Money"))){
			$this->getServer()->getLogger()->info("§8[§aWithdraw§8] §cNo economy plugin found.");
		}else{
			$this->getServer()->getLogger()->info("§8[§aWithdraw§8] §aFound an economy plugin! §bUsing: " . $this->money->getName());
		}
		$this->touch = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$mm = "§8[§aWithdraw§8] ";
		if(!$sender instanceof Player){
			$r = $mm . ("§cPlease run this command in game");
		}elseif(!is_numeric($sub[0]) || $sub[0] < 1){
			$r = $mm . $sub[0] . ("is an invalid number");
		}else{
			$sub[0] = floor($sub[0]);
			if($this->getMoney($sender) < $sub[0]){
				$r = $mm . ("§cYou don't have enough money! Your current money : §e$") . $this->getMoney($sender);
			}else{
				$this->giveMoney($sender, -$sub[0]);
				$sender->getInventory()->addItem(Item::get(339, $sub[0], 1));
				$r = $mm . ("§aYou withdrew a cheque of §e$" . $sub[0] . ". §aYour money : §e$") . $this->getMoney($sender);
			}
		}
		if(isset($r)) $sender->sendMessage($r);
		return true;
	}

	public function PlayerItemHeld(PlayerItemHeldEvent $ev){
        $item = $ev->getItem();
        $money = $item->getDamage();
        $player = $ev->getPlayer();
        if($item instanceof Item){
            switch($item->getId()){
                case 339:
                    $player->sendTip("§b§lCheque of §a$$money");
                break;
            }
        }
   }
	public function onPlayerInteract(PlayerInteractEvent $event){
		$p = $event->getPlayer();
		$i = $event->getItem();
		if($i->getID() !== 339 || ($money = $i->getDamage()) < 1) return;
		$m = "§8[§aWithdraw§8] ";
		if(!isset($this->touch[$n = $p->getName()])) $this->touch[$n] = 0;
		$c = microtime(true) - $this->touch[$n];
		if($c > 0){
			$m .= ("§bIf you want to use this check, tap again! \n §bCheque Info : §e$" . $money .);
		}else{
			$i->setCount($i->getCount() - 1);
			$p->getInventory()->setItem($p->getInventory()->getHeldItemSlot(), $i);
			$this->giveMoney($p, $money);
			$m .= ("§bSuccessfully redeemed cheque! \n §a§l+$" . $money .);
		}
		$this->touch[$n] = microtime(true) + 1;
		if(isset($m)) $p->sendMessage($m);
		$event->setCancelled();
	}

	public function getMoney($p){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
			case "MassiveEconomy":
				return $this->money->getMoney($p);
			break;
			case "EconomyAPI":
				return $this->money->mymoney($p);
			break;
			case "Money":
				return $this->money->getMoney($p->getName());
			break;
			default:
				return false;
			break;
		}
	}

	public function giveMoney($p, $money){
		if(!$this->money) return false;
		switch($this->money->getName()){
			case "PocketMoney":
				$this->money->grantMoney($p, $money);
			break;
			case "EconomyAPI":
				$this->money->setMoney($p, $this->money->mymoney($p) + $money);
			break;
			case "MassiveEconomy":
				$this->money->setMoney($p, $this->money->getMoney($p) + $money);
			break;
			case "Money":
				$n = $p->getName();
				$this->money->setMoney($n, $this->money->getMoney($n) + $money);
			break;
			default:
				return false;
			break;
		}
		return true;
	}
}
