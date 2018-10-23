<?php
declare(strict_types=1);
namespace jasonwynn10\ScoreboardAPI;

use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\Player;
use pocketmine\Server;

class Scoreboard {
	public const MAX_LINES = 15;
	public const SORT_ASCENDING = 0;
	public const SORT_DESCENDING = 1;
	public const SLOT_LIST = "list";
	public const SLOT_SIDEBAR = "sidebar";
	public const SLOT_BELOWNAME = "belowname"; //not working in 1.7.0.2
	/** @var string */
	protected $objectiveName = "";
	/** @var string */
	protected $displayName = "";
	/** @var string */
	protected $displaySlot = self::SLOT_SIDEBAR;
	/** @var int */
	protected $sortOrder = self::SORT_ASCENDING;
	/** @var int */
	protected $scoreboardId = 0;
	/** @var ScoreboardEntry[] */
	protected $entries = [];
	/** @var int $entryCount */
	protected $entryCount = 0;
	/** @var string[][] $entryViewers */
	protected $entryViewers = [];

	/**
	 * Scoreboard constructor.
	 *
	 * @param string $objectiveName
	 * @param string $displayName
	 * @param string $displaySlot
	 * @param int $sortOrder
	 * @param int $scoreboardId
	 */
	public function __construct(string $objectiveName, string $displayName, string $displaySlot, int $sortOrder, int $scoreboardId) {
		$this->objectiveName = $objectiveName;
		$this->displayName = $displayName;
		$this->displaySlot = $displaySlot;
		$this->sortOrder = $sortOrder;
		$this->scoreboardId = $scoreboardId;
	}

	/**
	 * @param int $line
	 * @param int $score
	 * @param int $type
	 * @param int|string $identifier use entity unique id if type is player or entity
	 *
	 * @return ScoreboardEntry
	 */
	public function createEntry(int $line, int $score, int $type = ScoreboardEntry::TYPE_FAKE_PLAYER, $identifier = "identifier") : ScoreboardEntry {
		if($line > self::MAX_LINES or $line < 0) {
			throw new \OutOfRangeException("Entry line number must be in range 0-15");
		}
		return new ScoreboardEntry($this, $line, $score, $type, $identifier);
	}

	/**
	 * @param ScoreboardEntry $data
	 * @param Player[] $players
	 *
	 * @return Scoreboard
	 */
	public function addEntry(ScoreboardEntry $data, array $players = []) : Scoreboard {
		if($data->objectiveName !== $this->objectiveName) {
			throw new \UnexpectedValueException("Scoreboard entry data does not match Scoreboard data");
		}
		if($data->scoreboardId - $this->scoreboardId > self::MAX_LINES or $data->scoreboardId - $this->scoreboardId < 0) {
			throw new \OutOfRangeException("Scoreboard entry line number is out of range 0-15");
		}
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_CHANGE;
		$this->entries[] = $pk->entries[] = $data;
		if(!empty($players)) {
			foreach($players as $player) {
				$this->entryViewers[$data->objectiveName ?? $data->entityUniqueId][] = $player->getName();
				$player->sendDataPacket($pk);
			}
		}else {
			foreach(ScoreboardAPI::getInstance()->getScoreboardViewers($this) as $player) {
				$this->entryViewers[$data->objectiveName ?? $data->entityUniqueId][] = $player->getName();
				$player->sendDataPacket($pk);
			}
		}
		return $this;
	}

	/**
	 * @param ScoreboardEntry $data
	 * @param Player[] $players
	 *
	 * @return Scoreboard
	 */
	public function removeEntry(ScoreboardEntry $data, array $players = []) : Scoreboard {
		if($data->objectiveName !== $this->objectiveName) {
			throw new \UnexpectedValueException("Scoreboard entry data does not match Scoreboard data");
		}
		if($data->scoreboardId - $this->scoreboardId > self::MAX_LINES or $data->scoreboardId - $this->scoreboardId < 0) {
			throw new \OutOfRangeException("Scoreboard entry line number is out of range 0-15");
		}
		$key = array_search($data, $this->entries);
		if($key !== false) {
			unset($this->entries[$key]);
		}
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_REMOVE;
		$pk->entries[] = $data;
		if(!empty($players)) {
			foreach($players as $player) {
				$key = array_search($player->getName(), $this->entryViewers[$data->customName ?? $data->entityUniqueId]);
				if($key !== false) {
					unset($this->entryViewers[$data->customName ?? $data->entityUniqueId][$key]);
				}
				$player->sendDataPacket($pk);
			}
		}else {
			foreach(ScoreboardAPI::getInstance()->getScoreboardViewers($this) as $player) {
				$key = array_search($player->getName(), $this->entryViewers[$data->customName ?? $data->entityUniqueId]);
				if($key !== false) {
					unset($this->entryViewers[$data->customName ?? $data->entityUniqueId][$key]);
				}
				$player->sendDataPacket($pk);
			}
		}
		return $this;
	}

	/**
	 * Automatically pads any custom text entries according to score digit count
	 */
	public function padEntries() : void {
		$entries = [];
		$maxSpaces = 1;
		foreach($this->entries as $entry) {
			if($entry->type !== ScoreboardEntry::TYPE_FAKE_PLAYER) {
				continue;
			}
			$entries[] = $entry;
			$digitCount = strlen((string)$entry->score);
			if($maxSpaces < $digitCount) {
				$maxSpaces = $digitCount;
			}
			$this->removeEntry($entry);
		}
		foreach($entries as $entry) {
			if($entry->customName{strlen($entry->customName)} !== " ")
				$entry->customName = str_pad($entry->customName, $maxSpaces - strlen((string)$entry->score));
			$this->addEntry($entry);
		}
	}

	/**
	 * @return string
	 */
	public function getObjectiveName() : string {
		return $this->objectiveName;
	}

	/**
	 * @param string $objectiveName
	 *
	 * @return Scoreboard
	 */
	public function setObjectiveName(string $objectiveName) : Scoreboard {
		$this->objectiveName = $objectiveName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayName() : string {
		return $this->displayName;
	}

	/**
	 * @param string $displayName
	 *
	 * @return Scoreboard
	 */
	public function setDisplayName(string $displayName) : Scoreboard {
		$this->displayName = $displayName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplaySlot() : string {
		return $this->displaySlot;
	}

	/**
	 * @param string $displaySlot
	 *
	 * @return Scoreboard
	 */
	public function setDisplaySlot(string $displaySlot) : Scoreboard {
		$this->displaySlot = $displaySlot;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSortOrder() : int {
		return $this->sortOrder;
	}

	/**
	 * @param int $sortOrder
	 *
	 * @return Scoreboard
	 */
	public function setSortOrder(int $sortOrder) : Scoreboard {
		$this->sortOrder = $sortOrder;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getScoreboardId() : int {
		return $this->scoreboardId;
	}

	/**
	 * @param int $scoreboardId
	 *
	 * @return Scoreboard
	 */
	public function setScoreboardId(int $scoreboardId) : Scoreboard {
		$this->scoreboardId = $scoreboardId;
		return $this;
	}

	/**
	 * @return ScoreboardEntry[]
	 */
	public function getEntries() : array {
		return $this->entries;
	}

	/**
	 * @param ScoreboardEntry $entry
	 *
	 * @return ScoreboardEntry[]
	 */
	public function getEntryViewers(ScoreboardEntry $entry) : array {
		$return = [];
		foreach($this->entryViewers[$entry->customName ?? $entry->entityUniqueId] as $name) {
			$player = Server::getInstance()->getPlayer($name);
			if($player !== null) {
				$return[] = $player;
			}
		}
		return $return;
	}
}