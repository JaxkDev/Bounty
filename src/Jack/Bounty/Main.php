<?php

/*
*   Bounty Pocketmine Plugin
*   Copyright (C) 2019-2021 JaxkDev
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

declare(strict_types=1);
namespace Jack\Bounty;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;


class Main extends PluginBase implements Listener{

    public const DATA_VER = 1;
    public const CONFIG_VER = 2;

    public $economy;
    public $dataFile;
    public $data;
    public $configd;

    public EventListener $eventListener;
	
	public function onEnable(){
		$this->economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		$this->saveResource("help.txt");
		//TODO SQL
		$this->dataFile = new Config($this->getDataFolder() . "data.yml", Config::YAML, ["version" => 1, "bounty" => []]);
		$this->data = $this->dataFile->getAll();
		$this->configd = $this->getConfig()->getAll();
		if(!array_key_exists("version", $this->configd) or $this->configd["version"] !== $this::CONFIG_VER){
			$this->updateConfig();
			$this->getLogger()->debug("Updated config to version ".$this::CONFIG_VER);
		}
		if(!array_key_exists("version", $this->data) or $this->data["version"] !== $this::DATA_VER){
			$this->updateData();
			$this->getLogger()->debug("Updated data to version ".$this::DATA_VER);
		}
		if(strtolower($this->configd["listType"]) !== "blacklist" && strtolower($this->configd["listType"]) !== "whitelist"){
			$this->getLogger()->error("Unknown listType '{$this->configd["listType"]}' reset to default 'Blacklist'");
			$this->configd["listType"] = "Blacklist";
			$this->save(false, true);
		}
		$this->eventListener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($this->EventListener, $this);
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        return $this->eventListener->handleCommand($sender, $command, $label, $args);
    }

    public function hasBounty(string $username): bool{
        return isset($this->data['bounty'][strtolower($username)]);
    }

    public function getBounty(string $username): ?int{
        return $this->data['bounty'][strtolower($username)]??null;
    }

    public function setBounty(string $username, int $amount): void{
	    $this->data['bounty'][strtolower($username)] = $amount;
    }

    public function save($data = true, $cfg = false){
		if($data === true){
            $this->dataFile->setAll($this->data);
		    $this->dataFile->save();
        }
        if($cfg === true){
            $this->getConfig()->setAll($this->configd);
            $this->getConfig()->save();
        }
	}

	public function updateData() : void{
	    //This is very unlikely to change more then once or twice a year.

        $this->data["version"] = $this::DATA_VER;

        $this->save(true, false);
    }

	public function updateConfig() : void{
	    //This is going to be very long if not controlled... (todo either support only one version break, or find a new method possibly compare keys and then set default values.)

        $this->configd["version"] = $this::CONFIG_VER;
		$this->configd["listType"] = "blacklist";
		$this->configd["list"] = [];

        $this->save(false, true);
    }
}
