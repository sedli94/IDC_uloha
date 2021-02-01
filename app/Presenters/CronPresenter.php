<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\DailyMenu;
use App\Model\Dish;
use App\Model\TieMailRestaurant;
use Nette;
use App\ZomatoCommunication\RestaurantData;

/**
 * Crons
 * Class CronPresenter
 * @package App\Presenters
 */
final class CronPresenter extends BasePresenter {

	/**
	 * start Zomato synchronization
	 */
	public function actionSynchronize(): void {
		$restarantData = new RestaurantData($this->database, $this->modelRestaurant, $this->dailyMenu, $this->dish);
		$restarantData->reloadRestaurantData();
		$restarantData->reloadMenusData();
		die('Hotovo: ' . date('Y-m-d H:i:s'));
	}

	/**
	 * Send email for subscribers
	 * @throws Nette\Application\UI\InvalidLinkException
	 */
	public function actionEmails(): void {
		foreach ($this->modelMail->getTable() as $emailRow) {
			$email = $emailRow->email;
			$mail = new Nette\Mail\Message;
			$mail->setFrom('Denní menu <dennimenu@example.com>')
				->addTo($email)
				->setSubject('Denní menu: ' . (date('j. n. Y')));
			$html = $this->makeMenus($emailRow->related(TieMailRestaurant::$table));
			$html .= '<br><br><a href="'.$this->link('//Newsletter:unsubscribe', [$emailRow->id, md5($emailRow->id.'-'.$emailRow->date_insert)]).'">Odhlásit odběr</a><br><a href="'.$this->link('//Newsletter:edit', [$emailRow->id, md5($emailRow->id.'-'.$emailRow->date_insert)]).'">Upravit odběr</a>';
			$mail->setHtmlBody($html);
			$mailer = new Nette\Mail\SendmailMailer;
			$mailer->send($mail);

		}
		die('Hotovo: ' . date('Y-m-d H:i:s'));
	}

	/**
	 * Prepare HTML for emails
	 * @param Nette\Database\Table\GroupedSelection $tieData
	 * @return string HTML with restaurants daily menus
	 */
	private function makeMenus(\Nette\Database\Table\GroupedSelection $tieData): string {
		$html = '';
		foreach ($tieData as $tie) {
			$restaurant = $this->modelRestaurant->getTable()->get($tie->id_restaurant);
			$html .= '<h1>'.$restaurant->title.'</h1>';
			foreach ($restaurant->related(DailyMenu::$table)->where('start_date <= ? AND (end_date = "0000-00-00 00:00:00" OR end_date >= ?)', date('Y-m-d 23:59:59'), date('Y-m-d 00:00:00')) as $menu) {
				$html .= '<h2>'.$menu->name.'</h2>';
				$html .= '<table><tr><th>Pokrm</th><th>Cena</th></tr>';
				foreach ($menu->related(Dish::$table) as $dish) {
					$html .= '<tr><td>'.$dish->name.'</td><td>'.$dish->price.'</td></tr>';
				}
				$html .= '</table>';
			}
		}
		return $html;
	}
}

