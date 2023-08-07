<?php

/*
*   Bounty Pocketmine Plugin
*   Copyright (C) 2019-present JaxkDev
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
*   Discord :: JaxkDev#2698
*   Email   :: JaxkDev@gmail.com
*/

namespace JaxkDev\Bounty;

use JaxkDev\Bounty\Events\BountyAddEvent;
use JaxkDev\Bounty\Events\BountyClaimEvent;
use JaxkDev\Bounty\Events\BountyCreateEvent;
use JaxkDev\Bounty\Events\BountyRemoveEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class EventListener implements Listener{

    public Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function handleCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($cmd->getName() == "bounty"){
            if(!isset($args[0])){
                return false;
            }
            if(!$sender instanceof Player){
                $sender->sendMessage(TF::RED."Commands can only be run in-game");
                return true;
            }
            switch($args[0]){
                case 'list':
                    if(!isset($args[1]) || $args[1] == '1'){
                        if(count($this->plugin->data['bounty']) == 0){
                            $sender->sendMessage(TF::RED."Nobody has a bounty yet.");
                        } else {
                            if(count($this->plugin->data['bounty']) >= 5){
                                $tmp = array_slice($this->plugin->data['bounty'], 0, 5, true);
                            } else {
                                $tmp = array_slice($this->plugin->data['bounty'], 0, count($this->plugin->data['bounty']), true);
                            }
                            $data = TF::GREEN.'Page '.TF::RED."1".TF::GOLD."/".TF::RED.ceil(count($this->plugin->data['bounty'])/5)."\n";
                            foreach($tmp as $user => $price){
                                $data = $data.TF::GOLD.$user." -> ".TF::GREEN."$".$price."\n";
                            }
                            $sender->sendMessage($data);
                        }
                    } else {
                        if(is_nan(intval($args[1]))){
                            $sender->sendMessage(TF::RED."Not a valid page number.");
                        } else {
                            if(count($this->plugin->data['bounty']) == 0){
                                $sender->sendMessage(TF::RED."Nobody has a bounty yet.");
                            } else {
                                if(intval($args[1]) > ceil(count($this->plugin->data['bounty'])/5)){
                                    $sender->sendMessage(TF::RED."Theres only ".ceil(count($this->plugin->data['bounty'])/5). " Pages.");
                                } else {
                                    if(count($this->plugin->data['bounty']) >= intval($args[1])*5){
                                        $tmp = array_slice($this->plugin->data['bounty'], (intval($args[1])*5)-5, (intval($args[1])*5), true);
                                    } else {
                                        $tmp = array_slice($this->plugin->data['bounty'], (intval($args[1])*5)-5, count($this->plugin->data['bounty']), true);
                                    }
                                    $data = TF::GREEN.'Page '.TF::RED.$args[1].TF::GOLD."/".TF::RED.ceil(count($this->plugin->data['bounty'])/5)."\n";
                                    foreach($tmp as $user => $price){
                                        $data = $data.TF::GOLD.$user.TF::RED." -> ".TF::GREEN."$".$price."\n";
                                    }
                                    $sender->sendMessage($data);
                                }
                            }
                        }
                    }
                    return true;
                case 'credits':
                    $sender->sendMessage(TF::GOLD."=== Credits ===");
                    $sender->sendMessage(TF::GREEN."Developer: ".TF::RED."Jackthehack21");
                    return true;
                case 'help':
                    $sender->sendMessage(TF::GREEN."-- Bounty Help: --");
                    $sender->sendMessage(TF::GOLD."/bounty new <playername> <amount>");
                    if($sender->hasPermission("bounty.rem")) $sender->sendMessage(TF::GOLD."/bounty rem <playername>");
                    $sender->sendMessage(TF::GOLD."/bounty list <page>");
                    if($this->plugin->configd["leaderboard"] === true) $sender->sendMessage(TF::GOLD."/bounty leaderboard");
                    $sender->sendMessage(TF::GOLD."/bounty help");
                    $sender->sendMessage(TF::GOLD."/bounty credits");
                    break;
                case "add":
                case "new":
                    if(!$sender->hasPermission("bounty.new")){
                        $msg = $this->plugin->configd["bounty_noperms"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
                        break;
                    }
                    if(!isset($args[1]) || !isset($args[2])){
                        $sender->sendMessage(TF::RED."Usage: /bounty new <playername> <amount>");
                        break;
                    }
                    $noob = $this->plugin->getServer()->getOfflinePlayer($args[1]);
                    if($noob?->getFirstPlayed() === null){
                        $msg = $this->plugin->configd["bounty_new_notfound"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
                        return true;
                    }
                    if($this->plugin->configd["bounty_multiple"] === false && isset($this->plugin->data['bounty'][strtolower($noob->getName())])){
                        $msg = $this->plugin->configd["bounty_new_already"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
                        return true;
                    }

                    $amount = intval($args[2]);

                    if(is_nan($amount)){
                        $sender->sendMessage(TF::RED."Usage: /bounty new <playername> <AMOUNT>");
                        return true;
                    }

                    if($this->plugin->configd["bounty_limit_enforced"] === true){
                        $min = $this->plugin->configd["bounty_min"];
                        $max = $this->plugin->configd["bounty_max"];
                        $msg = str_replace("{MAX}", $max, str_replace("{MIN}", $min, $this->colour($this->plugin->configd["bounty_new_fundlimit"])));
                        if($amount > $max || $amount < $min){
                            if($msg !== "") $sender->sendMessage($msg);
                            return true;
                        }
                    }

                    if(isset($this->plugin->data["bounty"][strtolower($noob->getName())])){
                        if($this->plugin->configd["bounty_multiple"] === true){
                            //Add to existing:
                            //events:
                            $event = new BountyAddEvent($this->plugin, $sender, $noob, $amount);
                            $event->call();
                            if($event->isCancelled()){
                                $msg = $this->plugin->configd["bounty_multiple_cancelled"];
                                if($msg !== "") $sender->sendMessage($this->colour($msg));
                                return true;
                            }

                            $amount = $event->getAmount();

                            $this->plugin->economy->deduct($sender->getName(), $amount)->onCompletion(
                                function() use ($sender, $noob, $amount){
                                    $this->plugin->data['bounty'][strtolower($noob->getName())] = $this->plugin->data['bounty'][strtolower($noob->getName())]+$amount;
                                    $this->plugin->save();
                                    if($this->plugin->configd['bounty_multiple_success'] !== "") $sender->sendMessage($this->colour($this->plugin->configd['bounty_multiple_success']));
                                    foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                                        /** @var string $msg */
                                        $msg = str_replace("{TOTAL}", (string)$this->plugin->data['bounty'][strtolower($noob->getName())], str_replace('{SENDER}', $sender->getName(), str_replace('{AMOUNT}', (string)$amount, str_replace('{PLAYER}', $noob->getName(), $this->colour($this->plugin->configd["bounty_multiple_broadcast"])))));
                                        if($msg !== "") $player->sendMessage($msg);
                                    }
                                },
                                function() use ($sender){
                                    $msg = $this->plugin->configd["bounty_multiple_funds"];
                                    if($msg !== "") $sender->sendMessage($this->colour($msg));
                                }
                            );
                        }  else {
                            //already has bounty, and multiple disabled.
                            $msg = $this->plugin->configd["bounty_new_already"];
                            if($msg !== "") $sender->sendMessage($this->colour($msg));
                        }
                        return true;
                    }

                    //Create new:

                    //events:
                    $event = new BountyCreateEvent($this->plugin, $sender, $noob, $amount);
			        $event->call();
			        if($event->isCancelled()){
                        $msg = $this->plugin->configd["bounty_new_cancelled"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
				        return true;
                    }

                    $amount = $event->getAmount();

                    $this->plugin->economy->deduct($sender->getName(), $amount)->onCompletion(function() use ($sender, $noob, $amount){
                        $this->plugin->data['bounty'][strtolower($noob->getName())] = $this->plugin->data['bounty'][strtolower($noob->getName())]+$amount;
                        $this->plugin->save();
                        if($this->plugin->configd['bounty_new_success'] !== "") $sender->sendMessage($this->colour($this->plugin->configd['bounty_new_success']));
                        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                            $msg = str_replace('{SENDER}', $sender->getName(), str_replace('{AMOUNT}', (string)$amount, str_replace('{PLAYER}', $noob->getName(), $this->colour($this->plugin->configd["bounty_new_broadcast"]))));
                            if($msg !== "") $player->sendMessage($msg);
                        }
                    }, function() use ($sender){
                        $msg = $this->plugin->configd["bounty_new_funds"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
                    });
                    return true;

                case "rem":
                case "remove":
                    if(!$sender->hasPermission("bounty.rem")){
                        $msg = $this->plugin->configd["bounty_noperms"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
                        break;
                    }
                    if(!isset($args[1])){
                        $sender->sendMessage(TF::RED."Usage: /bounty rem <playername>");
                        break;
                    }
                    if(count($this->plugin->data['bounty']) == 0 || !is_int($this->plugin->data["bounty"][strtolower($args[1])])){
                        $msg = $this->plugin->configd["bounty_rem_notfound"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
                        break;
                    }

                    //events:
                    $event = new BountyRemoveEvent($this->plugin, $sender, $args[1], $this->plugin->data["bounty"][strtolower($args[1])]);
			        $event->call();
			        if($event->isCancelled()){
                        $msg = $this->plugin->configd["bounty_rem_cancelled"];
                        if($msg !== "") $sender->sendMessage($this->colour($msg));
				        return true;
                    }

                    foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                        $msg = str_replace('{SENDER}', $sender->getName(),str_replace('{PLAYER}',$args[1],$this->colour($this->plugin->configd["bounty_rem_broadcast"])));
                        if($msg !== "") $player->sendMessage($msg);
                    }

                    unset($this->plugin->data["bounty"][strtolower($args[1])]);
                    $this->plugin->save();
                    if($this->plugin->configd["bounty_rem_success"] !== "") $sender->sendMessage($this->colour($this->plugin->configd["bounty_rem_success"]));
                    break;

                case 'leaderboard':
                case 'lb':
                    if($this->plugin->configd["leaderboard"] !== true) return true;
                    if(count($this->plugin->data['bounty']) == 0){
                        $sender->sendMessage("Nobody has a bounty.");
                        return true;
                    }
                    switch($this->plugin->configd["leaderboard_format"]){
                        case "form":
                            $form = new Form();
                            $form->data = [
                                "type" => "custom_form",
                                "title" => TF::AQUA.TF::BOLD." -- Bounty Leaderboard --",
                                "content" => []
                            ];
                            $form->data["content"][] = ["type" => "label", "text" => TF::GOLD.TF::BOLD."Name : Amount"];
                            $lb = $this->plugin->data['bounty'];
                            asort($lb);
                            $lb = array_reverse($lb);
                            $count = 1;
                            foreach($lb as $name => $amount){
                                $form->data["content"][] = ["type" => "label", "text" => TF::GREEN.$count.". ".TF::AQUA.$name." : $".$amount];
                                $count += 1;
                            }
                            $sender->sendForm($form);
                            break;
                        case "text":
                        case "chat":
                            $prefix = TF::BOLD.TF::GOLD."-- Bounty Leaderboard --";
                            $msg = "";
                            $lb = $this->plugin->data['bounty'];
                            asort($lb);
                            $lb = array_reverse($lb);
                            $count = 1;
                            foreach($lb as $name => $amount){
                                $msg = $msg.TF::GREEN.$count.". ".TF::AQUA.$name." : $".$amount."\n";
                                $count += 1;
                            }
                            $sender->sendMessage($prefix);
                            $sender->sendMessage($msg);
                            break;
                        default:
                            $sender->sendMessage("Not a valid option in config.yml, try using 'form' option.");
                    }
                    return true;
                default:
                    $sender->sendMessage(TF::RED."Invalid Command, Try /bounty help");
                    break;
            }
            return true;
        }
        return true;
    }

    public function onSpawn(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        if(isset($this->plugin->data["bounty"][strtolower($player->getName())])){
            /** @var string $msg */
            $msg = str_replace("{AMOUNT}", (string)$this->plugin->data["bounty"][strtolower($player->getName())], str_replace("{PLAYER}", $player->getName(), $this->plugin->configd["bounty_player_join"]));
            if(trim($msg) === "") return;
            foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
                $p->sendMessage($this->colour(trim($msg)));
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
        if(isset($this->plugin->data["bounty"][strtolower($player->getName())])){
            /** @var string $msg */
            $msg = str_replace("{AMOUNT}", (string)$this->plugin->data["bounty"][strtolower($player->getName())], str_replace("{PLAYER}", $player->getName(), $this->plugin->configd["bounty_player_quit"]));
            if($msg === "") return;
            foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
                $p->sendMessage($this->colour($msg));
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event): void{
		//TODO test projectile owning entity #15
		$killer = null;
        $cause = $event->getEntity()->getLastDamageCause();
		//if($cause === null) return;
        //if ($cause->getCause() != EntityDamageEvent::CAUSE_ENTITY_ATTACK or $cause->getCause() != EntityDamageEvent::CAUSE_PROJECTILE) return; //not killed by entity/projectile like arrow.
        if(!$cause instanceof EntityDamageByEntityEvent) return;
        if ($cause->getDamager() instanceof Player) {
            $killer = $cause->getDamager();
		}
		if ($cause->getDamager() instanceof Entity){
			if ($cause->getDamager()->getOwningEntity() instanceof Player){
				$killer = $cause->getDamager()->getOwningEntity();
			}
		}
		if ($killer instanceof Player){
            if(isset($this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())])){
                //events:
                $ev = new BountyClaimEvent($this->plugin, $killer, $event->getPlayer(), $this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())]);
			    $ev->call();
		        if($ev->isCancelled()){
                    if($this->plugin->configd["bounty_claim_cancelled"] !== "") $killer->sendMessage($this->colour($this->plugin->configd["bounty_claim_cancelled"]));
				    return;
                }

                $this->plugin->economy->add($killer->getName(), $this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())])->onCompletion(
                    function() use ($event, $killer){
                        if($this->plugin->configd["bounty_claim_success"] !== ""){
                            /** @var string $msg */
                            $msg = str_replace('{AMOUNT}', (string)$this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())], str_replace('{PLAYER}', $event->getPlayer()->getName(), $this->plugin->configd["bounty_claim_success"]));
                            $killer->sendMessage($this->colour($msg));
                        }
                        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                            /** @var string $msg */
                            $msg = str_replace("{PLAYER}", $event->getPlayer()->getName(), str_replace("{SENDER}", $killer->getName(), str_replace("{AMOUNT}", $this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())], $this->plugin->configd["bounty_claim_broadcast"])));
                            if($msg !== "") $player->sendMessage($this->colour($msg));
                        }

                        unset($this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())]);
                        $this->plugin->save();
                    },
                    function() use ($event, $killer){
                        if($this->plugin->configd["bounty_claim_failed"] !== ""){
                            /** @var string $msg */
                            $msg = str_replace('{AMOUNT}', (string)$this->plugin->data["bounty"][strtolower($event->getPlayer()->getName())], str_replace('{PLAYER}', $event->getPlayer()->getName(), $this->plugin->configd["bounty_claim_failed"]));
                            $killer->sendMessage($this->colour($msg));
                        }
                        //TODO Retry??? idk
                    }
                );
            }
        }
    }

    public function colour(string $msg): string{
        $colour = array("{BLACK}","{DARK_BLUE}","{DARK_GREEN}","{DARK_AQUA}","{DARK_RED}","{DARK_PURPLE}","{GOLD}","{GRAY}","{DARK_GRAY}","{BLUE}","{GREEN}","{AQUA}","{RED}","{LIGHT_PURPLE}","{YELLOW}","{WHITE}","{OBFUSCATED}","{BOLD}","{STRIKETHROUGH}","{UNDERLINE}","{ITALIC}","{RESET}");
        $keys = array(TF::BLACK, TF::DARK_BLUE, TF::DARK_GREEN, TF::DARK_AQUA, TF::DARK_RED, TF::DARK_PURPLE, TF::GOLD, TF::GRAY, TF::DARK_GRAY, TF::BLUE, TF::GREEN, TF::AQUA, TF::RED, TF::LIGHT_PURPLE, TF::YELLOW, TF::WHITE, TF::OBFUSCATED, TF::BOLD, TF::STRIKETHROUGH, TF::UNDERLINE, TF::ITALIC, TF::RESET);
        return str_replace(
            $colour,
            $keys,
            $msg
        );
    }
}
