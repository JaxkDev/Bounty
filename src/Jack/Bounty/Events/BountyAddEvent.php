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

namespace Jack\Bounty\Events;

use pocketmine\OfflinePlayer;
use pocketmine\Player;
use Jack\Bounty\Main;

class BountyAddEvent extends BountyEvent{
    
    private Player $player;

    /** @var OfflinePlayer|Player */
    private $wanted_player;

    private int $amount;

    /**
     * @param Main $plugin
     * @param Player $player
     * @param OfflinePlayer|Player $wanted_player
     * @param int $amount
     */
	public function __construct(Main $plugin, Player $player, $wanted_player, int $amount){
        parent::__construct($plugin);
	    $this->player = $player;
        $this->wanted_player = $wanted_player;
        $this->amount = $amount;
    }

    public function getPlayer(): Player{
        return $this->player;
	}

    /**
     * @return OfflinePlayer|Player
     */
    public function getWantedPlayer(){
        return $this->wanted_player;
    }

    public function getAmount(): int{
        return $this->amount;
    }

    public function setAmount(int $amount): void{
        $this->amount = $amount;
    }
}