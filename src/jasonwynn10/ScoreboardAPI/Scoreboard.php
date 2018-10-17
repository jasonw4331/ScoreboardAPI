<?php
declare(strict_types=1);
namespace jasonwynn10\ScoreboardAPI;

use pocketmine\network\mcpe\protocol\SetScorePacket;

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

	/**
	 * Scoreboard constructor.
	 *
	 * @param string $objectiveName
	 * @param string $displayName
	 * @param string $displaySlot
	 * @param string $sortOrder
	 * @param int $scoreboardId
	 */
	public function __construct(string $objectiveName, string $displayName, string $displaySlot, string $sortOrder, int $scoreboardId) {
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
	 * @param string $identifier use entity unique id if type is player or entity
	 *
	 * @return ScoreboardEntry
	 */
	public function createEntry(int $line, int $score, int $type, string $identifier) : ScoreboardEntry {
		return new ScoreboardEntry($this, $line, $score, $type, $identifier);
	}

	/**
	 * @param ScoreboardEntry $data
	 *
	 * @return Scoreboard
	 */
	public function addEntry(ScoreboardEntry $data) : Scoreboard {
		if($data->objectiveName !== $this->objectiveName) {
			throw new \UnexpectedValueException("Scoreboard entry data does not match Scoreboard data");
		}
		if($data->scoreboardId - $this->scoreboardId > self::MAX_LINES or $data->scoreboardId - $this->scoreboardId < 0) {
			throw new \OutOfRangeException("Scoreboard entry line number is out of range 0-15");
		}
		$this->entries[] = $data;
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_CHANGE;
		$pk->entries[] = $data;
		foreach(ScoreboardAPI::getInstance()->getScoreboardViewers($this) as $viewer) {
			$viewer->sendDataPacket($pk);
		}
		return $this;
	}

	/**
	 * @param ScoreboardEntry $data
	 *
	 * @return Scoreboard
	 */
	public function removeEntry(ScoreboardEntry $data) : Scoreboard {
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
		foreach(ScoreboardAPI::getInstance()->getScoreboardViewers($this) as $viewer) {
			$viewer->sendDataPacket($pk);
		}
		return $this;
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
}