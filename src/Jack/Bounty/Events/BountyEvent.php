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

namespace Jack\Bounty\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;

use Jack\Bounty\Main;

abstract class BountyEvent extends PluginEvent implements Cancellable{
    public function __construct(Main $plugin){
        parent::__construct($plugin);
    }
    
}