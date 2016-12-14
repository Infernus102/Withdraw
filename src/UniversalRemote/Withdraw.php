<?php

namespace UniversalRemote;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerItemHeldEvent, PlayerInteractEvent};
use pocketmine\Player; //Unneeded??
use pocketmine\item\Item;

class Withdraw extends PluginBase implements Listener{

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$pm = $this->getServer()->getPluginManager();
		$this->prefix = TF::DARK_GRAY."[".TF::GOLD."Withdraw".TF::DARK_GRAY."] ";
		$this->money = null;

		if ($pm->getPlugin("EconomyAPI") !== null){
			$this->money = $pm->getPlugin("EconomyAPI");
		} elseif ($pm->getPlugin("EconomyPlus") !== null){
			$this->money = $pm->getPlugin("EconomyPlus");
		}
		
		if($this->money == null){
			$this->getServer()->getLogger()->info($this->prefix.TF::RED."/withdraw disabled. Make sure you have EconomyAPI or EconomyPlus installed.");
		}else{
			$this->getServer()->getLogger()->info($this->prefix.TF::GREEN."Found an economy plugin! Using: ".$this->money->getName());
		}
		
		$this->touch = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) {
			$sender->sendMessage($this->prefix.TF::GRAY."/withdraw ".TF::YELLOW."<$>\n".TF::GRAY."This will create a cheque of value <$>.");
			return;
		}
		if(!$sender instanceof Player){
			$sender->sendMessage(TF::RED."Please run this command in game.");
		}elseif(!is_numeric($sub[0]) || $sub[0] < 1){
			$sender->sendMessage(TF::RED.$sub[0]."is an invalid number.");
		}else{
			if($this->getMoney($sender) < $sub[0]){
				$sender->sendMessage($this->prefix.TF::RED."You dont have enough money! Your current money: $".$this->getMoney($sender));
			}else{
				$this->giveMoney($sender, -$sub[0]);
				$sender->getInventory()->addItem(Item::get(339, $sub[0], 1));
				$sender->sendMessage($this->prefix.TF::GREEN."You withdrew a cheque of ".TF::YELLOW."$".$sub[0]."\n".TF::GREEN."Your money: ".TF::YELLOW."$".$this->getMoney($sender));
			}
		}
	}

 	public function PlayerItemHeld(PlayerItemHeldEvent $ev){
		$ss = $this->getConfig();
		$tip = $ss->get("tip");
		$item = $ev->getItem();
		$money = $item->getDamage();
		$player = $ev->getPlayer();
		if($item instanceof Item){
			switch($item->getId()){
				case 339:
					$player->sendPopup($tip." ".$money);
					break;
			}
		}
	}
	
	public function onInteract(PlayerInteractEvent $event){
		$p = $event->getPlayer();
		$i = $event->getItem();
		if($i->getID() !== 339 || ($money = $i->getDamage()) < 1) return;
		if(!isset($this->touch[$n = $p->getName()])) $this->touch[$n] = 0;
		$c = microtime(true) - $this->touch[$n];
		if($c > 0){
			$p->sendMessage($this->prefix.TF::GREEN."If you want to use this cheque, double tap it!\n".TF::GRAY."Cheque value: ".TF::YELLOW."$".$money);
		}else{
			$i->setCount($i->getCount() - 1);
			$p->getInventory()->setItem($p->getInventory()->getHeldItemSlot(), $i);
			$this->giveMoney($p, $money);
			$p->sendMessage($this->prefix.TF::GREEN."+ $".$money);
		}
		$this->touch[$n] = microtime(true) + 1;
		$event->setCancelled();
	}

	public function getMoney($p){
		if($this->money == null) return false;
		switch($this->money->getName()){
			case "EconomyAPI":
				return $this->money->myMoney($p);
			break;
			case "EconomyPlus":
				return $this->money->getInstance()->getMoney($p);
			break;
			default:
				return false;
			break;
		}
	}

	public function giveMoney($p, $money){
		if($this->money == null) return false;
		switch($this->money->getName()){
			case "EconomyAPI":
				$this->money->addMoney($p, $money);
			break;
			case "EconomyPlus":
				$this->money->getInstance()->setMoney($p, $this->money->getInstance()->getMoney($p) + $money);
			break;
			default:
				return false;
			break;
		}
		return true;
	}
}
