<?php

declare(strict_types=1);

namespace App\ZomatoCommunication;

use App\Model\DailyMenu;
use App\Model\Dish;
use Nette;
use App\Model\Restaurant;


final class RestaurantData {
	private static string $apiURL = 'https://developers.zomato.com/api/v2.1/';
	private static string $userKey = '00ae0bdd39ec5929d4200a5a21a3769b';
	private Nette\Database\Connection $database;
	private Restaurant $modelRestaurant;
	private DailyMenu $modelMenu;
	private Dish $modelDish;

	/**
	 * RestaurantData constructor.
	 * @param Nette\Database\Connection $database
	 * @param Restaurant $modelRestaurant
	 * @param DailyMenu $modelMenu
	 * @param Dish $modelDish
	 */
	public function __construct(Nette\Database\Connection $database, Restaurant $modelRestaurant, DailyMenu $modelMenu, Dish $modelDish) {
		$this->database = $database;
		$this->modelRestaurant = $modelRestaurant;
		$this->modelMenu = $modelMenu;
		$this->modelDish = $modelDish;
	}

	/**
	 * Function reload main data (name, phone, url, ...) for all Restaurant from table restaurants witch have active_for_download=1 and deactive all with active_for_download=0.
	 * Restaurants witch are not in Zomato API will be deactivate
	 */
	public function reloadRestaurantData(): void {
		$this->database->query('UPDATE `restaurants` SET`active`=`active_for_download` WHERE `active_for_download` = 0');
		foreach ($this->modelRestaurant->getAllActiveForDownload() as $restaurant) {
			$params = array('res_id' => $restaurant->res_id);
			$restaurantData = $this->callApi('restaurant', $params);
			if ($restaurantData['code'] == '200') {
				$this->saveRestaurant($restaurant, $restaurantData);
			} else {
				$restaurant->update(array(
					'active' => 0,
				));
			}
		}
	}

	/**
	 * Function reload daily menus for all Restaurant from table restaurants witch have active=1.
	 */
	public function reloadMenusData(): void {
		$this->modelMenu->getTable()->update(array('updated' => 0));
		$this->modelDish->getTable()->update(array('updated' => 0));
		foreach ($this->modelRestaurant->getAllActive() as $restaurant) {
			$params = array('res_id' => $restaurant->res_id);
			$dailyMenus = $this->callApi('dailymenu', $params);

			if ($dailyMenus['code'] == '200') {
				foreach ($dailyMenus['daily_menus'] as $dailyMenu) {
					$this->saveMenu($restaurant, $dailyMenu['daily_menu']);
				}
			}
		}
		$this->modelDish->getTable()->where('updated = ?', 0)->delete();
		$this->modelMenu->getTable()->where('updated = ?', 0)->delete();
	}

	/**
	 * Function for communication with Zomato API
	 * @param string $function called function
	 * @param array $paramsArray array with params for API
	 * @return array Json decoded API respons with added result code
	 */
	private function callApi(string $function, array $paramsArray): array {
		$params = http_build_query($paramsArray);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$apiURL . $function . '?' . $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

		$headers = array();
		$headers[] = 'Accept: application/json';
		$headers[] = 'user-key: ' . self::$userKey;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$result_array = (json_decode($result, true));
		$result_array['code'] = $http_code;
		return $result_array;
	}

	/**
	 * Function save restaurant data to database
	 * @param Nette\Database\Table\ActiveRow $restaurant
	 * @param array $restaurantData
	 */
	private function saveRestaurant(Nette\Database\Table\ActiveRow $restaurant, array $restaurantData): void {
		$restaurant->update(array(
			'title' => $restaurantData['name'],
			'city' => $restaurantData['location']['city'],
			'address' => $restaurantData['location']['address'],
			'phone_numbers' => $restaurantData['phone_numbers'],
			'url' => $restaurantData['url'],
			'active' => 1,
		));
	}

	/**
	 * Function save menus data to database
	 * @param Nette\Database\Table\ActiveRow $restaurant
	 * @param array $menuData
	 */
	private function saveMenu(Nette\Database\Table\ActiveRow $restaurant, array $menuData): void {
		$dailyMenu = null;
		foreach ($this->modelMenu->getTable()->where('daily_menu_id = ?', $menuData['daily_menu_id']) as $row) {
			$dailyMenu = $row;
		}
		if (!is_null($dailyMenu)) {
			$dailyMenu->update(array(
				'start_date' => $menuData['start_date'],
				'end_date' => isset($menuData['end_date']) ? $menuData['end_date'] : '0000-00-00 00:00:00',
				'name' => $menuData['name'],
				'id_restaurant' => $restaurant->id,
				'updated' => 1,
			));
		} else {
			$dailyMenu = $this->modelMenu->getTable()->insert(array(
				'daily_menu_id' => $menuData['daily_menu_id'],
				'start_date' => $menuData['start_date'],
				'end_date' => isset($menuData['end_date']) ? $menuData['end_date'] : '0000-00-00 00:00:00',
				'name' => $menuData['name'],
				'id_restaurant' => $restaurant->id,
				'updated' => 1,
			));
		}
		foreach ($menuData['dishes'] as $dish) {
			$this->saveDish($dailyMenu, $dish['dish']);
		}
	}

	/**
	 * Function save dishes data to database
	 * @param Nette\Database\Table\ActiveRow $dailyMenu
	 * @param array $dishData
	 */
	private function saveDish(Nette\Database\Table\ActiveRow $dailyMenu, array $dishData): void {
		$dish = null;
		foreach ($this->modelDish->getTable()->where('dish_id = ?', $dishData['dish_id']) as $row) {
			$dish = $row;
		}
		if (!is_null($dish)) {
			$dish->update(array(
				'name' => $dishData['name'],
				'price' => $dishData['price'],
				'daily_menu_id' => $dailyMenu->id,
				'updated' => 1,
			));
		} else {
			$this->modelDish->getTable()->insert(array(
				'dish_id' => $dishData['dish_id'],
				'name' => $dishData['name'],
				'price' => $dishData['price'],
				'daily_menu_id' => $dailyMenu->id,
				'updated' => 1,
			));
		}
	}
}

