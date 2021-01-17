<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

class TieMailRestaurant {
	use Nette\SmartObject;
	private Nette\Database\Explorer $database;
	static public string $table = 'tie_email_restaurant';

	public function __construct(Nette\Database\Explorer $database) {
		$this->database = $database;
	}

	public function getTable(): Nette\Database\Table\Selection {
		return $this->database->table(self::$table);
	}
}