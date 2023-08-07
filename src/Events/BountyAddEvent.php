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

namespace JaxkDev\Bounty\Events;

use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use JaxkDev\Bounty\Main;

class BountyAddEvent extends BountyEvent{
    
    private Player $player;

    private OfflinePlayer|Player $wanted_player;

    private int $amount;

	public function __construct(Main $plugin, Player $player, Player|OfflinePlayer $wanted_player, int $amount){
        parent::__construct($plugin);
	    $this->player = $player;
        $this->wanted_player = $wanted_player;
        $this->amount = $amount;
    }

    public function getPlayer(): Player{
        return $this->player;
	}

    public function getWantedPlayer(): Player|OfflinePlayer{
        return $this->wanted_player;
    }

    public function getAmount(): int{
        return $this->amount;
    }

    public function setAmount(int $amount): void{
        $this->amount = $amount;
    }
}