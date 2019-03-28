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

use Jack\Bounty\EventListener;


class Main extends PluginBase implements Listener{
    private static $instance;
	
	public function onEnable(){
		self::$instance = $this;
        $this->economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		if($this->economy == null){
		$this->getLogger()->info('Plugin disabled, could not find EconomyAPI.');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
		}
        $this->saveResource("config.yml");
        $this->saveResource("help.txt");
        $this->configFile = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->dataFile = new Config($this->getDataFolder() . "data.yml", Config::YAML, ["bounty" => []]);
        $this->data = $this->dataFile->getAll();
        $this->config = $this->configFile->getAll();
		if($this->config["version"] !== 1){
            //$this->fixConfig(); when v2 comes along
            $this->getLogger()->error("You messed with config.yml Plugin Disabled. (To fix this undo all changes or delete config.yml)");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->EventListener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->EventListener, $this);
        return;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        return $this->EventListener->handleCommand($sender, $cmd, $label, $args);
    }

    public function hasBounty(string $nick){
        if(isset($this->data['bounty'][$nick])){
            return "Theres a bounty on you";       //todo move around to API, think this was used privately for hud...
        } else {
            return "";
        }
    }

    public function getBounty(string $nick){
        if(!isset($this->data['bounty'][$nick])){
            return 0;
        }
        return $this->data['bounty'][$nick];
    }

    public function save(bool $data = true, bool $cfg = false){
		if($data === true){
            $this->dataFile->setAll($this->data);
		    $this->dataFile->save();
        }
        if($cfg === true){
            $this->configFile->setAll($this->config);
            $this->configFile->save();
        }
	}

    /*public function fixConfig(){
        $oldConfig = $this->data;
    }*/
	
    public static function getInstance() : self{
	    return self::$instance;
    }

}
