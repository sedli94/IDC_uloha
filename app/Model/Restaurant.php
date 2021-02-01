<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

class Restaurant {
	use Nette\SmartObject;
	private Nette\Database\Explorer $database;
	static public string $table = 'restaurants';

	public function __construct(Nette\Database\Explorer $database) {
		$this->database = $database;
	}

	public function getAllActiveForDownload(): Nette\Database\Table\Selection {
		return $this->database->table(self::$table)->where('active_for_download = ?', 1)->order('title ASC');
	}

	public function getAllActive(): Nette\Database\Table\Selection {
		return $this->database->table(self::$table)->where('active = ?', 1)->order('title ASC');
	}

	public function getTable(): Nette\Database\Table\Selection {
		return $this->database->table(self::$table);
	}

	public function setActiveBeforeImport():void {
		$this->database->query('UPDATE `restaurants` SET`active`=`active_for_download` WHERE `active_for_download` = 0');
	}
}