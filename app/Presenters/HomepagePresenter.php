<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\DailyMenu;
use App\Model\Dish;

/**
 * Homepage with daily menus for restaurants
 * Class HomepagePresenter
 * @package App\Presenters
 */
final class HomepagePresenter extends BasePresenter {

	/**
	 * function prepare all restaurations for view
	 */
	public function renderDefault(): void {
		$restaurants = array();
		foreach ($this->modelRestaurant->getAllActive() as $restaurant) {
			$restaurants[] = $restaurant->id;
		}
		$this->template->restaurants = $restaurants;
	}
}

