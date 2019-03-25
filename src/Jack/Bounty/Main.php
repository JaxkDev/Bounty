<?php
declare(strict_types=1);
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
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent, PlayerDeathEvent};;


class Main extends PluginBase implements Listener{
    private static $instance;
	public function onEnable(){
		self::$instance = $this;
        $this->eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		if($this->eco == null){
			$this->getLogger()->info('Plugin disabled, couldnt find EconomyAPI');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
		}
        $this->config = new Config($this->getDataFolder() . "data.yml", Config::YAML, ["version" => 1, "bounty" => []]);
        $this->data = $this->config->getAll();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
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
            case 'list':
                if(!isset($args[1]) || $args[1] == '1'){
                    if(count($this->data['bounty']) == 0){
                        $sender->sendMessage(C::RED."Nobody has a bounty yet.");
                        return true;
                    } else {
                        $tmp = null;
                        if(count($this->data['bounty']) >= 5){
                            $tmp = array_slice($this->data['bounty'], 0, 5, true);
                        } else {
                            $tmp = array_slice($this->data['bounty'], 0, count($this->data['bounty']), true);
                        }
                        $data = C::GREEN.'Page '.C::RED."1".C::GOLD."/".C::RED.ceil(count($this->data['bounty'])/5)."\n";
                        foreach($tmp as $user => $price){
                            $data = $data.C::GOLD.$user." -> ".C::GREEN."$".$price."\n";
                        }
                        $sender->sendMessage($data);
                        return true;
                    }
                } else {
                    if(is_nan(intval($args[1]))){
                        $sender->sendMessage(C::RED."Not a valid page number.");
                        return true;
                    } else {
                        if(count($this->data['bounty']) == 0){
                            $sender->sendMessage(C::RED."Nobody has a bounty yet.");
                            return true;
                        } else {
                            if(intval($args[1]) > ceil(count($this->data['bounty'])/5)){
                                $sender->sendMessage(C::RED."Theres only ".ceil(count($this->data['bounty'])/5). " Pages.");
                                return true;
                            } else {
                                $tmp = null;
                                if(count($this->data['bounty']) >= intval($args[1])*5){
                                    $tmp = array_slice($this->data['bounty'], (intval($args[1])*5)-5, (intval($args[1])*5), true);
                                } else {
                                    $tmp = array_slice($this->data['bounty'], (intval($args[1])*5)-5, count($this->data['bounty']), true);
                                }
                                $data = C::GREEN.'Page '.C::RED.$args[1].C::GOLD."/".C::RED.ceil(count($this->data['bounty'])/5)."\n";
                                foreach($tmp as $user => $price){
                                    $data = $data.C::GOLD.$user.C::RED." -> ".C::GREEN."$".$price."\n";
                                }
                                $sender->sendMessage($data);
                                return true;
                            }
                        }
                    }
                }
            case 'credits':
                $sender->sendMessage(C::GOLD."Credits:");
                $sender->sendMessage(C::AQUA."Developer: ".C::RED."Jackthehack21");
                return true;
			case 'version':
			case 'ver':
				$sender->sendMessage(C::GOLD."=== DETAILS ===");
				$sender->sendMessage(C::GREEN."Name     ".C::GOLD.":: ".C::AQUA."Bounty");
				$sender->sendMessage(C::GREEN."Build    ".C::GOLD.":: ".C::AQUA."1040");
				$sender->sendMessage(C::GREEN."Version  ".C::GOLD.":: ".C::AQUA."1.0.3");
				$sender->sendMessage(C::GREEN."Release  ".C::GOLD.":: ".C::AQUA."Public Release - 1.0.3");
				break;
		    case 'help':
                $sender->sendMessage(C::GREEN."-- Bounty Help: --");
                $sender->sendMessage(C::GOLD."/bounty new <playername> <amount>");
                $sender->sendMessage(C::GOLD."/bounty list <page>");
                $sender->sendMessage(C::GOLD."/bounty help");
                $sender->sendMessage(C::GOLD."/bounty version");
                $sender->sendMessage(C::GOLD."/bounty credits");
                break;
            case 'new':
                if(!isset($args[1]) || !isset($args[2])){
                    $sender->sendMessage(C::RED."/bounty new <playername> <amount>");
                    break;
                }
                $noob = $this->getServer()->getOfflinePlayer($args[1]);
				if(!is_numeric($noob->getFirstPlayed())){
					$sender->sendMessage(C::RED."Error > Player not found");
					return true;
                }
                if(isset($this->data['bounty'][$noob->getName()])){
                    $sender->sendMessage(C::RED."That user already has a bounty");
                    return true;
                }
                $mon = $this->eco->myMoney($sender->getName());
                if(is_nan(intval($args[2]))){
                    $sender->sendMessage(C::RED."/bounty new <playername> <AMOUNT>");
                    return true;
                }
                if(intval($args[2]) > $mon){
                    $sender->sendMessage(C::RED."You placed a bounty of ".$args[2]." but you dont have that much, check by typing /mymoney");
                    return true;
                }
                $this->eco->reduceMoney($sender->getName(), intval($args[2]));
                $this->data['bounty'][$noob->getName()] = intval($args[2]);
                $this->save();
                $sender->sendMessage('Bounty Added !');
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    $player->sendMessage(str_replace('{money}', $args[2],str_replace('{player}',$noob->getName(),C::AQUA."{player} Has a bount worth $"."{money}, Kill them to get the reward.")));
                }
                return true;
            default:
                $sender->sendMessage(C::RED."Invalid Command, Try /bounty help");
                break;
		}
		return true;
        }
    }

    public function hasBounty(string $nick){
        if(isset($this->data['bounty'][$nick])){
            return "Theres a bounty on you";
        } else {
            return "";
        }
    }

    public function getBounty(string $nick){
        if(!isset($this->data['bounty'][$nick])){
            return "";
        }
        return $this->data['bounty'][$nick];
    }

    public function save(){
		$this->config->setAll($this->data);
		$this->config->save();
	}

    public function onDeath(PlayerDeathEvent $event){
        $cause = $event->getEntity()->getLastDamageCause(); // Thanks IZeoGamer !
        if ($cause->getCause() != 1) return; //not killed by entity
        if (!$cause instanceof EntityDamageByEntityEvent) return; //double check
        if ($cause->getDamager() instanceof Player) {
            $killer = $cause->getDamager();
            if(isset($this->data["bounty"][$event->getPlayer()->getName()])){
                $killer->sendMessage("Nice one you got $".$this->data["bounty"][$event->getPlayer()->getName()]." for killing ".$event->getPlayer()->getName()." who had a bounty on his/her head !");
                $this->eco->addMoney($killer->getName(), $this->data["bounty"][$event->getPlayer()->getName()]);
                unset($this->data["bounty"][$event->getPlayer()->getName()]);
                $this->save();
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    $player->sendMessage(C::GOLD.'Bounty for '.$event->getPlayer()->getName().' has been claimed by '.$killer->getName());
                }
            }
        }
    }
	
    public static function getInstance() : self{
	return self::$instance;
    }

}
