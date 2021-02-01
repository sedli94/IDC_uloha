<?php

declare(strict_types=1);

namespace App\Presenters;

use App\components\RestaurantMenuControl;
use App\Model\DailyMenu;
use App\Model\Dish;
use App\Model\Email;
use App\Model\Restaurant;
use App\Model\TieMailRestaurant;
use Nette;

/**
 * Class BasePresenter
 * @package App\Presenters
 */
class BasePresenter extends Nette\Application\UI\Presenter {
	protected Nette\Database\Connection $database;
	protected Restaurant $modelRestaurant;
	protected DailyMenu $dailyMenu;
	protected Dish $dish;
	protected Email $modelMail;
	protected TieMailRestaurant $modelTie;

	/**
	 * BasePresenter constructor.
	 * @param Restaurant $modelRestaurant
	 * @param DailyMenu $dailyMenu
	 * @param Dish $dish
	 * @param Email $modelMail
	 * @param TieMailRestaurant $modelTie
	 */
	public function __construct(Restaurant $modelRestaurant, DailyMenu $dailyMenu, Dish $dish, Email $modelMail, TieMailRestaurant $modelTie) {
		$this->modelRestaurant = $modelRestaurant;
		$this->dailyMenu = $dailyMenu;
		$this->dish = $dish;
		$this->modelMail = $modelMail;
		$this->modelTie = $modelTie;
	}

	/**
	 * Component for view restaurant menu
	 * @return RestaurantMenuControl
	 */
	protected function createComponentRestaurantMenu(): RestaurantMenuControl
	{
		$restaurantMenu = new RestaurantMenuControl($this->modelRestaurant);
		return $restaurantMenu;
	}
}

