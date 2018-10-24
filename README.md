# ScoreboardAPI
[![Discord](https://img.shields.io/badge/chat-on%20discord-7289da.svg)](https://discord.gg/tZQMhBQ)
[![Poggit-Ci](https://poggit.pmmp.io/ci.shield/jasonwynn10/ScoreboardAPI/ScoreboardAPI)](https://poggit.pmmp.io/ci/jasonwynn10/ScoreboardAPI/ScoreboardAPI)
[![Download count](https://poggit.pmmp.io/shield.dl.total/ScoreboardAPI)](https://poggit.pmmp.io/p/ScoreboardAPI)

## Basic API
#### Imports
You will want all of these classes imported where you are using ScoreboardAPI.
```php
use jasonwynn10\ScoreboardAPI\Scoreboard;
use jasonwynn10\ScoreboardAPI\ScoreboardAPI;
use jasonwynn10\ScoreboardAPI\ScoreboardEntry;
```

#### Creating a scoreboard
Scoreboard instances can be created through the ScoreboardAPI class.
```php
/** @var PluginBase $this */
$api = $this->getServer()->getPluginManager()->getPlugin("ScoreboardAPI");
$scoreboard = $api->createScoreboard("objective", "Scoreboard Title", "sidebar", "ascending");
```
This method also has default values for the most common uses. E.g. the sidebar in ascending order.
```php
/** @var PluginBase $this */
$api = $this->getServer()->getPluginManager()->getPlugin("ScoreboardAPI");
$scoreboard = $api->createScoreboard("objective", "Scoreboard Title"); // assumes sidebar in ascending order
```

#### Adding an entry
Scoreboards aren't just titles. They need something to fill their lines!

Entries can be created and added to scoreboards through the Scoreboard instance.
```php
$line = 1; // line number
$score = 1; // current score
$type = ScoreboardEntry::TYPE_FAKE_PLAYER; // other types are TYPE_PLAYER and TYPE_ENTITY
$identifier = "line 1"; // this is a string for fake players but must be an entity id for other types
/** @var Scoreboard $scoreboard */
$entry = $scoreboard->createEntry($line, $score, $type, $identifier);
$scoreboard->addEntry($entry);
```
Once an entry as been added to the scoreboard, all scoreboard viewers will automatically be able to see it.

#### Removing an Entry
Entries can be removed from scoreboards through the Scoreboard instance.
```php
/** @var Scoreboard $scoreboard */
$entry = $scoreboard->createEntry(1, 1, ScoreboardEntry::TYPE_FAKE_PLAYER, "Line 1");
$scoreboard->addEntry($entry); // entry added

$scoreboard->removeEntry($entry); // remove entry
```
Once an entry as been removed from the scoreboard, all scoreboard viewers will automatically be able to see it.

#### Updating an entry
So now you have your entries all on the board, but you need to change one.

Entries can be updated by removing the existing entry, then re-adding it.
```php
/** @var Scoreboard $scoreboard */
$scoreboard->removeEntry($entry); // remove old entry
$entry->score++; // update score
$entry->customName = "Line ".$entry->score; // update custom name
$scoreboard->addEntry($entry); // add updated entry
```

#### Sending a scoreboard
Now that you've prepared your scoreboard, it needs sent to the players!

Scoreboards can be sent through the ScoreboardAPI class.
```php
/** @var PluginBase $this */
$api = $this->getServer()->getPluginManager()->getPlugin("ScoreboardAPI");
$scoreboard = $api->createScoreboard("objective", "Scoreboard Title", "sidebar", "ascending");

$api->sendScoreboard($scoreboard); // send scoreboard to everyone
```

#### Deleting a scoreboard
Let's say you don't want the scoreboard to show to people anymore.

Scoreboards can be removed through the ScoreboardAPI class.
```php
/** @var PluginBase $this */
$api = $this->getServer()->getPluginManager()->getPlugin("ScoreboardAPI");
$scoreboard = $api->createScoreboard("objective", "Scoreboard Title", "sidebar", "ascending");
$api->sendScoreboard($scoreboard); // scoreboard sent to everyone

$api->removeScoreboard($scoreboard); // remove scoreboard from everyone
```

## Advanced API
#### Scoreboard Viewers
In `ScoreboardAPI::sendScoreboard()`, the second parameter can be set for specific scoreboard viewers to be added.
```php
/** @var PluginBase $this */
$api = $this->getServer()->getPluginManager()->getPlugin("ScoreboardAPI");
$scoreboard = $api->createScoreboard("objective", "Scoreboard Title", "sidebar", "ascending");

/** @var Player $player */
$api->sendScoreboard($scoreboard, [$player]); // scoreboard sent to player
```

Like sending, the second parameter in `ScoreboardAPI::removeScoreboard()` can be set for specific scoreboard viewers to be removed.
```php
/** @var PluginBase $this */
$api = $this->getServer()->getPluginManager()->getPlugin("ScoreboardAPI");
$scoreboard = $api->createScoreboard("objective", "Scoreboard Title", "sidebar", "ascending");
/** @var Player $player */
$api->sendScoreboard($scoreboard); // scoreboard sent to everyone

$api->removeScoreboard($scoreboard, [$player]); // scoreboard removed from player
```
#### Entry Viewers
Entry viewers can be set when adding the entry to the scoreboard via the second parameter of `Scoreboard::addEntry()`
```php
$line = 1; // line number
$score = 1; // current score
$type = ScoreboardEntry::TYPE_FAKE_PLAYER; // other types are TYPE_PLAYER and TYPE_ENTITY
$identifier = "line 1"; // this is a string for fake players but must be an entity id for other types
/** @var Scoreboard $scoreboard */
$entry = $scoreboard->createEntry($line, $score, $type, $identifier);
/** @var Player $player */
$scoreboard->addEntry($entry, [$player]);
```

Players can be specified for removing the entry via the second parameter of `Scoreboard::removeEntry()`
```php
$line = 1; // line number
$score = 1; // current score
$type = ScoreboardEntry::TYPE_FAKE_PLAYER; // other types are TYPE_PLAYER and TYPE_ENTITY
$identifier = "line 1"; // this is a string for fake players but must be an entity id for other types
/** @var Scoreboard $scoreboard */
$entry = $scoreboard->createEntry($line, $score, $type, $identifier);
/** @var Player $player */
$scoreboard->addEntry($entry, [$player]);
```

Once an entry as been added or removed, all specified viewers will be able to see the changes immediately.
#### Scoreboard Padding
Padding the scoreboard will offset the text of each entry from the score with the most digits. Padding does not affect entries which use entity ids.
```php
/** @var Scoreboard $scoreboard */
$scoreboard->padEntries();
```
#### Entry Padding
Entry padding can only be done with Fake Player types. Padding offsets the text from the score by the score with the most digits in the scoreboard.
```php
/** @var ScoreboardEntry $entry */
$entry->pad();
```