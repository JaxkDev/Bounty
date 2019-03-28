<?php

/*
*   Bounty Pocketmine Plugin
*   Copyright (C) 2019 Jackthehack21 (Jack Honour/Jackthehaxk21/JaxkDev)
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <https://www.gnu.org/licenses/>.
*
*   Twitter :: @JaxkDev
*   Discord :: Jackthehaxk21#8860
*   Email   :: gangnam253@gmail.com
*/

declare(strict_types=1);

namespace Jack\Bounty;

use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\OfflinePlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\event\player\{PlayerJoinEvent,PlayerDeathEvent};;
use pocketmine\network\mcpe\protocol\{SetScorePacket, RemoveObjectivePacket, SetDisplayObjectivePacket};;

use Jack\Bounty\Main;
use Jack\Bounty\Form;

use Jack\Bounty\Events\{BountyClaimEvent,BountyAddEvent,BountyRemEvent};;


class EventListener implements Listener{

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function handleCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($cmd->getName() == "bounty"){
            if(!isset($args[0])){
                return false;
            }
            switch($args[0]){
                case 'list':
                    if(!isset($args[1]) || $args[1] == '1'){
                        if(count($this->plugin->data['bounty']) == 0){
                            $sender->sendMessage(C::RED."Nobody has a bounty yet.");
                            return true;
                        } else {
                            $tmp = null;
                            if(count($this->plugin->data['bounty']) >= 5){
                                $tmp = array_slice($this->plugin->data['bounty'], 0, 5, true);
                            } else {
                                $tmp = array_slice($this->plugin->data['bounty'], 0, count($this->plugin->data['bounty']), true);
                            }
                            $data = C::GREEN.'Page '.C::RED."1".C::GOLD."/".C::RED.ceil(count($this->plugin->data['bounty'])/5)."\n";
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
                            if(count($this->plugin->data['bounty']) == 0){
                                $sender->sendMessage(C::RED."Nobody has a bounty yet.");
                                return true;
                            } else {
                                if(intval($args[1]) > ceil(count($this->plugin->data['bounty'])/5)){
                                    $sender->sendMessage(C::RED."Theres only ".ceil(count($this->plugin->data['bounty'])/5). " Pages.");
                                    return true;
                                } else {
                                    $tmp = null;
                                    if(count($this->plugin->data['bounty']) >= intval($args[1])*5){
                                        $tmp = array_slice($this->plugin->data['bounty'], (intval($args[1])*5)-5, (intval($args[1])*5), true);
                                    } else {
                                        $tmp = array_slice($this->plugin->data['bounty'], (intval($args[1])*5)-5, count($this->plugin->data['bounty']), true);
                                    }
                                    $data = C::GREEN.'Page '.C::RED.$args[1].C::GOLD."/".C::RED.ceil(count($this->plugin->data['bounty'])/5)."\n";
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
                    $sender->sendMessage(C::GOLD."=== Credits ===");
                    $sender->sendMessage(C::GREEN."Developer: ".C::RED."Jackthehack21");
                    return true;
                case 'version':
                case 'ver':
                    $sender->sendMessage(C::GOLD."=== DETAILS ===");
                    $sender->sendMessage(C::GREEN."Name     ".C::GOLD.":: ".C::AQUA."Bounty");
                    $sender->sendMessage(C::GREEN."Release  ".C::GOLD.":: ".C::AQUA."Development - v1.1.0");
                    break;
                case 'help':
                    $sender->sendMessage(C::GREEN."-- Bounty Help: --");
                    $sender->sendMessage(C::GOLD."/bounty new <playername> <amount>");
                    if($sender->hasPermission("bounty.rem")) $sender->sendMessage(C::GOLD."/bounty rem <playername>");
                    $sender->sendMessage(C::GOLD."/bounty list <page>");
                    if($this->plugin->config["leaderboard"] === true) $sender->sendMessage(C::GOLD."/bounty leaderboard");
                    $sender->sendMessage(C::GOLD."/bounty help");
                    $sender->sendMessage(C::GOLD."/bounty version");
                    $sender->sendMessage(C::GOLD."/bounty credits");
                    break;
                case 'new':
                    if(!$sender->hasPermission("bounty.new")){
                        $sender->sendMessage(C::RED."You do not have permission to run that command.");
                        break;
                    }
                    if(!isset($args[1]) || !isset($args[2])){
                        $sender->sendMessage(C::RED."Usage: /bounty new <playername> <amount>");
                        break;
                    }
                    $noob = $this->plugin->getServer()->getOfflinePlayer($args[1]);
                    if(!is_numeric($noob->getFirstPlayed())){
                        $sender->sendMessage(C::RED."Error > Player not found");
                        return true;
                    }
                    if(isset($this->plugin->data['bounty'][strtolower($noob->getName())])){
                        $sender->sendMessage(C::RED."That user already has a bounty");
                        return true;
                    }
                    $mon = $this->plugin->economy->myMoney($sender->getName());

                    $amount = intval($args[2]);

                    if(is_nan($amount)){
                        $sender->sendMessage(C::RED."Usage: /bounty new <playername> <AMOUNT>");
                        return true;
                    }

                   //events:
                    $event = new BountyAddEvent($this->plugin, $sender, $noob, $amount);
			        $this->plugin->getServer()->getPluginManager()->callEvent($event);
			        if($event->isCancelled()){
                        $sender->sendMessage("Bounty cancelled.");
				        return true;
                    }

                    $amount = $event->getAmount();

                    if($amount > $mon){
                        $sender->sendMessage(C::RED."A bounty of ".$args[2]." has been requested, but you dont have that much ( check by typing /mymoney )");
                        return true;
                    }
                    $this->plugin->economy->reduceMoney($sender->getName(), $amount);
                    $this->plugin->data['bounty'][strtolower($noob->getName())] = $amount;
                    $this->plugin->save();
                    $sender->sendMessage('Bounty successfully added.');
                    foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                        $player->sendMessage(str_replace('{AMOUNT}', $amount,str_replace('{PLAYER}',$noob->getName(),C::AQUA.$this->plugin->config["bounty_new_broadcast"])));
                    }
                    if($this->plugin->config["leaderboard"] == true and $this->plugin->config["leaderboard_format"] == "scoreboard"){
                        $this->updateScoreboards();
                    }
                    return true;

                case "rem":
                case "remove":
                    if(!$sender->hasPermission("bounty.rem")){
                        $sender->sendMessage(C::RED."You do not have permission to run that command.");
                        break;
                    }
                    if(!isset($args[1])){
                        $sender->sendMessage(C::RED."Usage: /bounty rem <playername>");
                        break;
                    }
                    if(count($this->plugin->data['bounty']) == 0 || !is_int($this->plugin->data["bounty"][strtolower($args[1])])){
                        $sender->sendMessage(C::RED."Player not found, make sure you spelt the name correctly.");
                        break;
                    }

                    //events:
                    $event = new BountyRemEvent($this->plugin, $sender, $args[1], $this->plugin->data["bounty"][strtolower($args[1])]);
			        $this->plugin->getServer()->getPluginManager()->callEvent($event);
			        if($event->isCancelled()){
                        $sender->sendMessage("Bounty cancelled.");
				        return true;
                    }


                    unset($this->plugin->data["bounty"][strtolower($args[1])]);
                    $this->plugin->save();
                    $sender->sendMessage(C::GREEN."Bounty for ".$args[1]." has been removed !");
                    if($this->plugin->config["leaderboard"] == true and $this->plugin->config["leaderboard_format"] == "scoreboard"){
                        $this->updateScoreboards();
                    }
                    break;

                case 'leaderboard':
                case 'lb':
                    if($this->plugin->config["leaderboard"] !== true) return true;
                    if(count($this->plugin->data['bounty']) == 0){
                        $sender->sendMessage("Nobody has a bounty.");
                        return true;
                    }
                    switch($this->plugin->config["leaderboard_format"]){
                        case "form":
                            $form = new Form();
                            $form->data = [
                                "type" => "custom_form",
                                "title" => C::AQUA.C::BOLD." -- Bounty Leaderboard --",
                                "content" => []
                            ];
                            $form->data["content"][] = ["type" => "label", "text" => C::GOLD.C::BOLD."Name : Amount"];
                            $lb = $this->plugin->data['bounty'];
                            asort($lb);
                            $lb = array_reverse($lb);
                            $count = 1;
                            foreach($lb as $name => $amount){
                                $form->data["content"][] = ["type" => "label", "text" => C::GREEN.$count.". ".C::AQUA.$name." : $".$amount];
                                $count += 1;
                            }
                            $sender->sendForm($form);
                            break;
                        case "text":
                        case "chat":
                            $prefix = C::BOLD.C::GOLD."-- Bounty Leaderboard --";
                            $msg = C::AQUA."";
                            $lb = $this->plugin->data['bounty'];
                            asort($lb);
                            $lb = array_reverse($lb);
                            $count = 1;
                            foreach($lb as $name => $amount){
                                $msg = $msg.C::GREEN.$count.". ".C::AQUA.$name." : $".$amount."\n";
                                $count += 1;
                            }
                            $sender->sendMessage($prefix);
                            $sender->sendMessage($msg);
                            break;
                        case "scoreboard":
                            break;
                        default:
                            $sender->sendMessage("Not a valid option in config.yml, try using 'form' option.");
                    }
                    return true;
                default:
                    $sender->sendMessage(C::RED."Invalid Command, Try /bounty help");
                    break;
            }
            return true;
        }
    }

    public function onSpawn(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->config["leaderboard"] === true && $this->plugin->config["leaderboard_format"] === "scoreboard"){
            $this->sendScoreboard($player);
        }
    }

    public function onDeath(PlayerDeathEvent $event){
        $cause = $event->getEntity()->getLastDamageCause();
        if ($cause->getCause() != 1) return; //not killed by entity
        if (!$cause instanceof EntityDamageByEntityEvent) return; //double check of above check.
        if ($cause->getDamager() instanceof Player) {
            $killer = $cause->getDamager();
            if(isset($this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())])){

                //events:
                $ev = new BountyClaimEvent($this->plugin, $killer, $event->getPlayer(), $this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())]);
			    $this->plugin->getServer()->getPluginManager()->callEvent($ev);
		        if($ev->isCancelled()){
                    $sender->sendMessage("Bounty claim cancelled.");
				    return true;
                }

                $killer->sendMessage("Nice one you got $".$this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())]." for killing ".$event->getPlayer()->getLowerCaseName()." who had a bounty !");
                $this->plugin->economy->addMoney($killer->getName(), $this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())]);
                unset($this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())]);
                $this->plugin->save();
                foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                    $player->sendMessage(C::GOLD.'Bounty for '.$event->getPlayer()->getLowerCaseName().' has been claimed by '.$killer->getLowerCaseName());
                }
                if($this->plugin->config["leaderboard"] == true and $this->plugin->config["leaderboard_format"] == "scoreboard"){
                    $this->updateScoreboards();
                }
            }
        }
    }

    public function sendScoreboard(Player $player){
        $pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $player->getLowerCaseName();
		$pk->displayName = C::BOLD.C::GOLD." Bounty TOP 10 ";
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->sendDataPacket($pk);

        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
            
        if(count($this->plugin->data['bounty']) === 0){
            $pk2 = new ScorePacketEntry();
            $pk2->objectiveName = $player->getLowerCaseName();
            $pk2->type = 3;
            $pk2->customName = C::RED."No one has a bounty... Yet";
            $pk2->score = 0;
            $pk2->scoreboardId = 0;
                
            $pk->entries[] = $pk2;
        } else{
            $pk2 = new ScorePacketEntry();
            $pk2->objectiveName = $player->getLowerCaseName();
            $pk2->type = 3;
            $pk2->customName = "";
            $pk2->score = 0;
            $pk2->scoreboardId = 0;
            $pk->entries[] = $pk2;
                
            $lb = $this->plugin->data['bounty'];
            asort($lb);
            $lb = array_reverse($lb);
            $count = 1;
            foreach($lb as $name => $amount){
                if($count >= 11) continue;
                $pk2 = new ScorePacketEntry();
                $pk2->objectiveName = $player->getLowerCaseName();
                $pk2->type = 3;
                $pk2->customName = C::AQUA." ".$name." : $".$amount;
                $pk2->score = $count;
                $pk2->scoreboardId = $count;
                    
                $pk->entries[] = $pk2;
                $count += 1;
            }
        }

        $player->sendDataPacket($pk);
    }

    public function updateScoreboards(){
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            //printf("updating for %s\n",$player->getLowerCaseName());
            $pk = new RemoveObjectivePacket();
            $pk->objectiveName = $player->getLowerCaseName();  //edit lines doesnt want to play ball so hacky fix around it.
            $player->sendDataPacket($pk);
            $this->sendScoreboard($player);
        }
    }
}
