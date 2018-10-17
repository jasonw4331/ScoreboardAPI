<?php
declare(strict_types=1);
namespace jasonwynn10\ScoreboardAPI;

use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

class Scoreboard {
	public const MAX_LINES = 15;
	public const SORT_ASCENDING = 0;
	public const SORT_DESCENDING = 1;
	public const SLOT_LIST = "list";
	public const SLOT_SIDEBAR = "sidebar";
	public const SLOT_BELOWNAME = "belowname"; //not working in 1.7.0.2
	/** @var string */
	private $objectiveName;
	/** @var string */
	private $displayName;
	/** @var string */
	private $displaySlot;
	/** @var int */
	private $sortOrder;
	/** @var int */
	private $scoreboardId;
	/** @var ScorePacketEntry[] $entries */
	private $entries = [];

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
	 * @param ScorePacketEntry $data
	 *
	 * @return Scoreboard
	 */
	public function addEntry(ScorePacketEntry $data) : Scoreboard {
		if($data->objectiveName !== $this->objectiveName or $data->scoreboardId < $this->scoreboardId) {
			throw new \UnexpectedValueException("Entry data does not match Scoreboard data");
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
	 * @param ScorePacketEntry $data
	 *
	 * @return Scoreboard
	 */
	public function removeEntry(ScorePacketEntry $data) : Scoreboard {
		if($data->objectiveName !== $this->objectiveName or $data->scoreboardId < $this->scoreboardId) {
			throw new \UnexpectedValueException("Entry data does not match Scoreboard data");
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
	 * @return ScorePacketEntry[]
	 */
	public function getEntries() : array {
		return $this->entries;
	}
}