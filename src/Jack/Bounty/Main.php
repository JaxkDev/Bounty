<?php

namespace Jack\Bounty;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\OfflinePlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent, PlayerDeathEvent};;


class Main extends PluginBase implements Listener{

	public function onEnable(){
        if (!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
            //Use default, not PM.
        }
        $this->eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        $this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML, ["version" => 1, "bounty" => []]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("Bounty 1.0 Enabled");
        return;
	}
	
	public function onDisable(){
        $this->getLogger()->info("Bounty 1.0 Disabled");
        return;
    }

    /**
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($cmd->getName() == "bounty"){
        if(!isset($args[0])){
			//$sender->sendMessage(C::RED.$this->responses->get('invalid_command'));
            return false;
	    }
	    switch($args[0]){
			case 'version':
			case 'ver':
				$sender->sendMessage(C::GOLD."=== DETAILS ===");
				$sender->sendMessage(C::GREEN."Name     ".C::GOLD.":: ".C::AQUA."Bounty");
				$sender->sendMessage(C::GREEN."Build    ".C::GOLD.":: ".C::AQUA."1001");
				$sender->sendMessage(C::GREEN."Version  ".C::GOLD.":: ".C::AQUA."1.0");
				$sender->sendMessage(C::GREEN."Release  ".C::GOLD.":: ".C::AQUA."Public Release - 1.0");
				break;
		    case 'help':
                $sender->sendMessage(C::GREEN."-- Bounty Help: --");
                $sender->sendMessage(C::GOLD."/bounty new <playername> <amount>");
                $sender->sendMessage(C::GOLD."/bounty help");
                $sender->sendMessage(C::GOLD."/bounty version");
                break;
            case 'new':
                if(!isset($args[1]) || !isset($args[2])){
                    $sender->sendMessage(C::RED."/bounty new <playername> <amount>");
                    break;
                }
                $noob = $this->getServer()->getOfflinePlayer($args[1]);
				if(!is_numeric($noob->getFirstPlayed())){
					$sender->sendMessage(C::RED."Error > Player not found");
					return false;
                }
                if(isset($this->data['bounty'][$noob->getName()])){
                    $sender->sendMessage(C::RED."That user already has a bounty");
                    return false;
                }
                $mon = $this->eco->myMoney($sender->getName());
                if(is_nan(intval($args[2]))){
                    $sender->sendMessage(C::RED."/bounty new <playername> <AMOUNT>");
                    return false;
                }
                if(intval($args[2]) > $mon){
                    $sender->sendMessage(C::RED."You placed a bounty of ".$args[2]." but you only have  $".str_val($mon));
                    return false;
                }
                $this->eco->reduceMoney($sender->getName(), intval($args[2]));
                $this->data['bounty'][$noob->getName()] = intval($args[2]);
                $sender->sendMessage('Bounty Added !');
            default:
                $sender->sendMessage(C::RED."Invalid Command, Try /bounty help");
                break;
		}
		return true;
        }
    }

    #public function onDeath(PlayerDeathEvent $event){

    #}

}
