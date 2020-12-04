<?php
declare(strict_types=1);
namespace jasonwynn10\ScoreboardAPI;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class ScoreboardAPI extends PluginBase implements Listener {
	private static $instance;
	/** @var Scoreboard[] $scoreboards */
	private $scoreboards = [];
	/** @var int $scoreboardCount */
	private $scoreboardCount = 0;
	/** @var string[][] $scoreboardViewers */
	private $scoreboardViewers = [];

	/**
	 * @return ScoreboardAPI
	 */
	public static function getInstance() : self {
		return self::$instance;
	}

	public function onLoad() : void {
		self::$instance = $this;
	}

	/**
	 * Reset plugin if server is reloaded
	 */
	public function onDisable() {
		foreach($this->scoreboards as $scoreboard) {
			$this->removeScoreboard($scoreboard);
		}
		$this->scoreboardCount = 0;
		$this->scoreboards = [];
		$this->scoreboardViewers = [];
	}

	/**
	 * @param Scoreboard $scoreboard
	 * @param Player[] $players
	 *
	 * @return Scoreboard
	 */
	public function removeScoreboard(Scoreboard $scoreboard, array $players = []) : Scoreboard {
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $scoreboard->getObjectiveName();
		if(!empty($players)) {
			foreach($players as $player) {
				if($scoreboard->getDisplaySlot() === Scoreboard::SLOT_BELOWNAME) {
					$player->setScoreTag("");
				}
				$key = array_search($player->getName(), $this->scoreboardViewers[$scoreboard->getObjectiveName()]);
				if($key !== false) {
					unset($this->scoreboardViewers[$scoreboard->getObjectiveName()][$key]);
				}
				$player->getNetworkSession()->sendDataPacket($pk);
			}
		}else {
			foreach($this->getScoreboardViewers($scoreboard) as $player) {
				if($scoreboard->getDisplaySlot() === Scoreboard::SLOT_BELOWNAME) {
					$player->setScoreTag("");
				}
				$player->getNetworkSession()->sendDataPacket($pk);
			}
			unset($this->scoreboardViewers[$scoreboard->getObjectiveName()]);
		}
		return $scoreboard;
	}

	/**
	 * @param Scoreboard $scoreboard
	 *
	 * @return Player[] returns online players who can see the scoreboard
	 */
	public function getScoreboardViewers(Scoreboard $scoreboard) : array {
		$return = [];
		if(!isset($this->scoreboardViewers[$scoreboard->getObjectiveName()]))
			return [];
		foreach($this->scoreboardViewers[$scoreboard->getObjectiveName()] as $name) {
			$player = $this->getServer()->getPlayerExact($name);
			if($player !== null) {
				$return[] = $player;
			}
		}
		return $return;
	}

	/**
	 * @param string $objectiveName
	 * @param string $displayName
	 * @param string $displaySlot
	 * @param int $sortOrder
	 *
	 * @return Scoreboard
	 */
	public function createScoreboard(string $objectiveName, string $displayName, string $displaySlot = Scoreboard::SLOT_SIDEBAR, int $sortOrder = Scoreboard::SORT_ASCENDING) : Scoreboard {
		$this->scoreboardCount++;
		$this->scoreboardViewers[$objectiveName] = [];
		return $this->scoreboards[$objectiveName] = new Scoreboard($objectiveName, $displayName, $displaySlot, $sortOrder, $this->scoreboardCount);
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event) : void {
		foreach($this->scoreboardViewers as $objectiveName => $viewers) {
			if(in_array($event->getPlayer()->getName(), $viewers)) {
				$this->sendScoreboard($this->getScoreboard($objectiveName), [$event->getPlayer()]);
			}
		}
	}

	/**
	 * @param Scoreboard $scoreboard
	 * @param Player[] $players
	 *
	 * @return Scoreboard
	 */
	public function sendScoreboard(Scoreboard $scoreboard, array $players = []) : Scoreboard {
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = $scoreboard->getDisplaySlot();
		$pk->objectiveName = $scoreboard->getObjectiveName();
		$pk->displayName = $scoreboard->getDisplayName();
		$pk->criteriaName = "dummy"; // TODO
		$pk->sortOrder = $scoreboard->getSortOrder();
		$pk2 = new SetScorePacket();
		$pk2->type = SetScorePacket::TYPE_CHANGE;
		if(!empty($players)) {
			foreach($players as $player) {
				foreach($scoreboard->getEntries() as $entry) {
					if(in_array($player, $scoreboard->getEntryViewers($entry))) {
						$pk2->entries[] = $entry;
					}
				}
				if($scoreboard->getDisplaySlot() === Scoreboard::SLOT_BELOWNAME) {
					$player->setScoreTag($scoreboard->getDisplayName());
				}
				$this->scoreboardViewers[$scoreboard->getObjectiveName()][] = $player->getName();
				$player->getNetworkSession()->sendDataPacket($pk);
				$player->getNetworkSession()->sendDataPacket($pk2);
			}
		}else {
			foreach($this->getScoreboardViewers($scoreboard) as $player) {
				foreach($scoreboard->getEntries() as $entry) {
					if(in_array($player, $scoreboard->getEntryViewers($entry))) {
						$pk2->entries[] = $entry;
					}
				}
				if($scoreboard->getDisplaySlot() === Scoreboard::SLOT_BELOWNAME) {
					$player->setScoreTag($scoreboard->getDisplayName());
				}
				$this->scoreboardViewers[$scoreboard->getObjectiveName()][] = $player->getName();
				$player->getNetworkSession()->sendDataPacket($pk);
				$player->getNetworkSession()->sendDataPacket($pk2);
			}
		}
		return $scoreboard;
	}

	/**
	 * @param string $objectiveName
	 *
	 * @return Scoreboard|null
	 */
	public function getScoreboard(string $objectiveName) : ?Scoreboard {
		return $this->scoreboards[$objectiveName] ?? null;
	}
}