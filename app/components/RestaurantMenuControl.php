<?php

declare(strict_types=1);

namespace App\components;

use App\Model\DailyMenu;
use App\Model\Dish;
use App\Model\Restaurant;
use Nette\Application\UI\Control;

/**
 * View restaurant deily menu and basic information about restaurant
 * Class RestaurantMenuControl
 * @package App\components
 */
class RestaurantMenuControl extends Control {
	private Restaurant $modelRestaurant;

	/**
	 * RestaurantMenuControl constructor.
	 * @param Restaurant $modelRestaurant
	 */
	public function __construct(Restaurant $modelRestaurant) {
		$this->modelRestaurant = $modelRestaurant;
	}

	/**
	 * @param int $idRestaurant viewed restaurant
	 */
	public function render(int $idRestaurant): void {
		$restaurant = $this->modelRestaurant->getTable()->get($idRestaurant);
		$restaurantData = array(
			'restaurant' => $restaurant->toArray(),
			'daily_menus' => array(),
		);

		foreach ($restaurant->related(DailyMenu::$table)->where('start_date <= ? AND (end_date = "0000-00-00 00:00:00" OR end_date >= ?)', date('Y-m-d 23:59:59'), date('Y-m-d 00:00:00')) as $menu) {
			$restaurantData['daily_menus'][$menu->id] = array(
				'daily_menu' => $menu->toArray(),
				'dishes' => array(),
			);

			foreach ($menu->related(Dish::$table) as $dish) {
				$restaurantData['daily_menus'][$menu->id]['dishes'][$dish->id] = $dish->toArray();
			}
		}
		$this->template->restaurant = $restaurantData;
		$this->template->render(__DIR__ . '/templates/restaurantMenu.latte');
	}

}
