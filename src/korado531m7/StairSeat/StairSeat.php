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

use pocketmine\block\Air;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\block\Stair;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class StairSeat extends PluginBase{
    const CONFIG_VERSION = 4;

    /** @var SeatData[] */
    private $seatData = [];
    /** @var Config */
    private $toggleConfig;
    
    public function onEnable(){
        $this->toggleConfig = new Config($this->getDataFolder() . 'toggle.yml', Config::YAML);
        $this->reloadConfig();
        if(!$this->isCompatibleWithConfig()){
            $this->getLogger()->warning('Your configuration file is outdated. To update the config, please delete it at '.($this->getDataFolder() . 'config.yml'));
        }
        if($this->getConfig()->get('register-sit-command', true)){
            $this->getServer()->getCommandMap()->register($this->getName(), new ToggleCommand($this));
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function isEnabledStair(Block $block) : bool{
        $conf = $this->getConfig();
        $id = $block->getId();
        return (
            $block instanceof Stair &&
            ($conf->get('allow-seat-upsidedown') ? true : (($block->getDamage() & 0x04) === 0)) &&
            (
                ($id === Block::ACACIA_STAIRS && $conf->get('enable-stair-acasia', true)) ||
                ($id === Block::BIRCH_STAIRS && $conf->get('enable-stair-birch', true)) ||
                ($id === Block::BRICK_STAIRS && $conf->get('enable-stair-brick', true)) ||
                ($id === Block::COBBLESTONE_STAIRS && $conf->get('enable-stair-cobblestone', true)) ||
                ($id === Block::DARK_OAK_STAIRS && $conf->get('enable-stair-dark_oak', true)) ||
                ($id === Block::JUNGLE_STAIRS && $conf->get('enable-stair-jungle', true)) ||
                ($id === Block::NETHER_BRICK_STAIRS && $conf->get('enable-stair-nether_brick', true)) ||
                ($id === Block::OAK_STAIRS && $conf->get('enable-stair-oak', true)) ||
                ($id === Block::PURPUR_STAIRS && $conf->get('enable-stair-purpur', true)) ||
                ($id === Block::QUARTZ_STAIRS && $conf->get('enable-stair-quartz', true)) ||
                ($id === Block::RED_SANDSTONE_STAIRS && $conf->get('enable-stair-red_sandstone', true)) ||
                ($id === Block::SANDSTONE_STAIRS && $conf->get('enable-stair-sandstone', true)) ||
                ($id === Block::SPRUCE_STAIRS && $conf->get('enable-stair-spruce', true)) ||
                ($id === Block::STONE_BRICK_STAIRS && $conf->get('enable-stair-stone_brick', true)) ||
                ($id === Block::STONE_STAIRS && $conf->get('enable-stair-stone', true))
            )
        );
    }

    public function isAllowedHigherHeight() : bool{
        return (bool) $this->getConfig()->get('allow-seat-high-height', true);
    }

    public function isAllowedWhileSneaking() : bool{
        return (bool) $this->getConfig()->get('allow-seat-while-sneaking', true);
    }

    public function standWhenBreak() : bool{
        return (bool) $this->getConfig()->get('stand-up-when-break-block', true);
    }

    public function isToggleEnabled(Player $player) : bool{
        return (bool) $this->toggleConfig->get(strtolower($player->getName()));
    }

    public function canApplyWorld(Level $level) : bool{
        return ((bool) $this->getConfig()->get('apply-all-worlds', true)) ? true : (in_array($level->getFolderName(), array_map('trim', explode(',', (string) $this->getConfig()->get('apply-world-names', '')))));
    }

    public function isDisabledDamagesWhenSit() : bool{
        return (bool) $this->getConfig()->get('disable-damage-when-sit', false);
    }

    public function isEnabledCheckOnBlock() : bool{
        return (bool) $this->getConfig()->get('enable-check-up-block', false);
    }

    public function isAllowedOnlyRightClick() : bool{
        return (bool) $this->getConfig()->get('allow-only-right-click', false);
    }

    public function isDefaultToggleEnabled() : bool{
        return (bool) $this->getConfig()->get('default-toggle-sit', true);
    }

    public function checkClick(PlayerInteractEvent $event) : bool{
        return $this->isAllowedOnlyRightClick() ? ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) : ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK);
    }

    public function getToggleCommandLabel() : string{
        return (string) $this->getConfig()->get('toggle-command-label', 'sit');
    }

    public function canSit(Player $player, Block $block) : bool{
        return (
            ($this->isDefaultToggleEnabled() || $this->isToggleEnabled($player)) &&
            $this->canApplyWorld($block->getLevel()) &&
            $this->isEnabledStair($block) &&
            !$this->isSitting($player) &&
            ($this->isAllowedHigherHeight() || (!$this->isAllowedHigherHeight() && ($player->y >= $block->y))) &&
            ($this->isAllowedWhileSneaking() || (!$this->isAllowedWhileSneaking() && !$player->isSneaking())) &&
            (!$this->isEnabledCheckOnBlock() || ($this->isEnabledCheckOnBlock() && $block->getLevel()->getBlock($block->up()) instanceof Air))
        );
    }

    public function addSeatData(SeatData $data) : void{
        $this->seatData[] = $data;
    }

    public function getSeatDataByPlayer(Player $player) : ?SeatData{
        foreach($this->seatData as $seatDatum)
            if($player->getId() === $seatDatum->getPlayer()->getId())
                return $seatDatum;
        return null;
    }

    public function getSeatDataByPosition(Position $pos) : ?SeatData{
        foreach($this->seatData as $seatDatum)
            if($seatDatum->equals($pos))
                return $seatDatum;
        return null;
    }

    public function removeSeatDataByPosition(Position $pos) : bool{
        foreach($this->seatData as $key => $seatDatum)
            if($seatDatum->equals($pos)){
                $seatDatum->stand();
                unset($this->seatData[$key]);
                return true;
            }
        return false;
    }

    /**
     * Return player is sitting on the stairs.
     * Developers have to use this method to check whether player is sitting
     *
     * @param Player $player
     *
     * @return bool
     */
    public function isSitting(Player $player) : bool{
        return $this->getSeatDataByPlayer($player) instanceof SeatData;
    }

    /**
     * @return SeatData[]
     */
    public function getAllSeatData() : array{
        return $this->seatData;
    }

    public function getToggleConfig() : Config{
        return $this->toggleConfig;
    }

    private function isCompatibleWithConfig() : bool{
        return $this->getConfig()->get('config-version') == self::CONFIG_VERSION;
    }
}