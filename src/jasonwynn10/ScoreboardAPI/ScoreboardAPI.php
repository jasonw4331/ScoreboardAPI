<?php
declare(strict_types=1);
namespace jasonwynn10\ScoreboardAPI;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\Player;
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
	 * @param string $objectiveName
	 * @param string $displayName
	 * @param string $displaySlot
	 * @param string $sortOrder
	 * @param int $scoreboardId
	 *
	 * @return Scoreboard
	 */
	public function createScoreboard(string $objectiveName, string $displayName, string $displaySlot, string $sortOrder, int $scoreboardId = null) : Scoreboard {
		$this->scoreboardCount++;
		$this->scoreboardViewers[$objectiveName] = [];
		return $this->scoreboards[$objectiveName] = new Scoreboard($objectiveName, $displayName, $displaySlot, $sortOrder, $scoreboardId ?? $this->scoreboardCount);
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
				if($scoreboard->getDisplaySlot() === "belowname") {
					$player->setScoreTag("");
				}
				$key = array_search($player->getName(), $this->scoreboardViewers[$scoreboard->getObjectiveName()]);
				if($key !== false) {
					unset($this->scoreboardViewers[$scoreboard->getObjectiveName()][$key]);
				}
				$player->sendDataPacket($pk);
			}
		}else {
			foreach($this->getScoreboardViewers($scoreboard) as $player) {
				if($scoreboard->getDisplaySlot() === "belowname") {
					$player->setScoreTag("");
				}
				$player->sendDataPacket($pk);
			}
			unset($this->scoreboards[$scoreboard->getObjectiveName()]); // no more scoreboard because no players can see it
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
		foreach($this->scoreboardViewers[$scoreboard->getObjectiveName()] as $name) {
			$player = $this->getServer()->getPlayer($name);
			if($player !== null) {
				$return[] = $player;
			}
		}
		return $return;
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
		$pk->criteriaName = "dummy";
		$pk->sortOrder = $scoreboard->getSortOrder();
		$pk2 = new SetScorePacket();
		$pk2->type = SetScorePacket::TYPE_CHANGE;
		$pk2->entries = $scoreboard->getEntries();
		if(!empty($players)) {
			foreach($players as $player) {
				if($scoreboard->getDisplaySlot() === "belowname") {
					$player->setScoreTag($scoreboard->getDisplayName());
				}
				$this->scoreboardViewers[$scoreboard->getObjectiveName()][] = $player->getName();
				$player->sendDataPacket($pk);
				$player->sendDataPacket($pk2);
			}
		}else {
			foreach($this->getScoreboardViewers($scoreboard) as $player) {
				if($scoreboard->getDisplaySlot() === "belowname") {
					$player->setScoreTag($scoreboard->getDisplayName());
				}
				$this->scoreboardViewers[$scoreboard->getObjectiveName()][] = $player->getName();
				$player->sendDataPacket($pk);
				$player->sendDataPacket($pk2);
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