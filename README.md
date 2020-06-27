[![](https://poggit.pmmp.io/shield.dl.total/StairSeat)](https://poggit.pmmp.io/p/StairSeat)
[![](https://poggit.pmmp.io/shield.state/StairSeat)](https://poggit.pmmp.io/p/StairSeat)

# StairSeat
**Sit on the stair block**


## Feature
You can sit on the stair block


## How to use
Install this plugin and run your server.
place stair block and tap (or click) it.
To cancel, jump or sneak.


## Command
`/sit` - Toggle to sit on the stair block. To use this command, you must set `register-sit-command` in the config.yml to `true` (Permission: `stairseat.toggle`)

## Settings
You can customize some settings.
To edit, open config.yml in plugin folder.

### General
* `apply-worlds` - Enable to use stairs as seat for each worlds or all worlds.
To enable all worlds, set value to `true` , or if you want to enable specific worlds, type the worlds' name.
(To set multiple worlds, separate with comma like `world1, world2`)

* `allow-seat-high-height` - Allow player to sit on seat if its height is higher than player

* `allow-seat-upsidedown` - Allow player to sit on upside-down stairs

* `allow-seat-while-sneaking` - Allow to sit when player is sneaking

* `stand-up-when-break-block` - Player who sit on their stairs will stand up when break

* `disable-damage-when-sit` - While player is sitting on the stair, will not be damaged from all causes

* `register-sit-command` - Register /sit command


* `send-tip-when-sit` - This massage will be sent when player sit
  * `@b` - block's name
  * `@x` - x coordinates
  * `@y` - y coordinates
  * `@z` - z coordinates


* `try-to-sit-already-inuse` - Send message when trying to seat on stair which is already used
  * `@b` - block's name
  * `@p` - sitting player's name
  
  
### Developer Documentation
 * If you check whether player is sitting on the stair, please call method isSitting from StairSeat class
```php
/** @var \pocketmine\Player $player */
isSitting(Player $player) : bool
```