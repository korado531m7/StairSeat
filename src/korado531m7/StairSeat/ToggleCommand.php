<?php
/*
 * This file is part of StairSeat.
 *
 *  StairSeat is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  StairSeat is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with StairSeat.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace korado531m7\StairSeat;


use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ToggleCommand extends PluginCommand{
    public function __construct(Plugin $owner){
        parent::__construct('sit', $owner);
        $this->setDescription('Toggle to sit on the stairs');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($sender instanceof Player){
            $sender->sendMessage($this->toggle(strtolower($sender->getName())) ? (TextFormat::GREEN . 'You will be able to sit on the stairs') : (TextFormat::RED . 'You will not be able to sit on the stairs'));
        }else{
            $this->getPlugin()->getLogger()->info('You can use this command in-game');
        }
    }

    private function toggle(string $name) : bool{
        /** @var StairSeat $owner */
        $owner = $this->getPlugin();
        $conf = $owner->getToggleConfig();
        $next = ($conf->exists($name)) ? !$conf->get($name) : false;
        $conf->set($name, $next);
        $conf->save();
        return $next;
    }
}