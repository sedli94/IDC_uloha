<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\Email;
use App\Model\Restaurant;
use App\Model\TieMailRestaurant;
use Nette;
use Nette\Application\UI\Form;
use function _HumbugBox1aa671719151\Clue\React\Block\awaitAll;

/**
 * Add, remove and edit subscribe
 * Class NewsletterPresenter
 * @package App\Presenters
 */
final class NewsletterPresenter extends BasePresenter {

	/**
	 * Function remove email from db table for subscribers
	 * @param int $emilaId id of email
	 * @param string $hash checking hash
	 * @throws Nette\Application\AbortException
	 */
	public function actionUnsubscribe(int $emilaId, string $hash): void {
		$email = $this->modelMail->getTable()->get($emilaId);
		if (!$email) {
			$this->flashMessage('E-mail nenalezen', 'error');
			$this->redirect('Homepage:default');
		}
		if (md5($email->id . '-' . $email->date_insert) != $hash) {
			$this->flashMessage('Nesprávný odkaz', 'error');
			$this->redirect('Homepage:default');
		}
		$email->delete();
		$this->flashMessage('Odhlášení z odběru proběhlo úspěšně', 'success');
		$this->redirect('Homepage:default');
	}

	/**
	 * Function prepare values for form whitch edit followed restaurants for subscribers
	 * @param int $emilaId id of email
	 * @param string $hash checking hash
	 * @throws Nette\Application\AbortException
	 */
	public function actionEdit(int $emailId, string $hash): void {
		$email = $this->modelMail->getTable()->get($emailId);
		if (!$email) {
			$this->flashMessage('E-mail nenalezen', 'error');
			$this->redirect('Homepage:default');
		}
		if (md5($email->id . '-' . $email->date_insert) != $hash) {
			$this->flashMessage('Nesprávný odkaz', 'error');
			$this->redirect('Homepage:default');
		}
		$data = array(
			'email' => $email->email,
			'hash' => $hash,
			'restaurants' => array(),
		);
		foreach ($email->related(TieMailRestaurant::$table) as $row) {
			$data['restaurants'][] = $row->id_restaurant;
		}

		$this['subscribeForm']->setDefaults($data);
	}

	/**
	 * create form for add or update subscribe
	 * @return Form
	 */
	public function createComponentSubscribeForm(): Form {
		$form = new Form();
		$form->addEmail('email', 'e-mail')->setRequired();
		$form->addHidden('hash', '');
		$restaurants = array();
		foreach ($this->modelRestaurant->getAllActive() as $restaurant) {
			$restaurants[$restaurant->id] = $restaurant->title;
		}
		$form->addCheckboxList('restaurants', 'Restaurace k odběru', $restaurants);

		$form->addSubmit('send', 'přihlásit k odběru');
		$form->onSuccess[] = [$this, 'subscribeFormSucceeded'];
		return $form;
	}

	/**
	 * Function for save new or edited subscribe
	 * @param Form $form
	 * @param array $values
	 * @throws Nette\Application\AbortException
	 */
	public function subscribeFormSucceeded(Form $form, array $values): void {
		$emailId = $this->getParameter('emailId');
		if ($values['hash'] == '') {
			if (count($this->modelMail->getTable()->where('email = ?', $values['email'])) > 0) {
				$this->flashMessage('E-mail je již registrován', 'error');
				return;
			}
			$email = $this->modelMail->getTable()->insert(array('email' => $values['email']));
		} else {
			$email = $this->modelMail->getTable()->get($emailId);
			if (md5($email->id . '-' . $email->date_insert) != $values['hash']) {
				$this->flashMessage('Chyba dat', 'error');
				$this->redirect('Homepage:default');
			}
		}

		$this->modelTie->getTable()->where('id_email = ?', $email->id)->delete();
		foreach ($values['restaurants'] as $restaurantId) {
			$this->modelTie->getTable()->insert(array(
				'id_restaurant' => $restaurantId,
				'id_email' => $email->id,
			));
		}

		if ($values['hash'] == '') {
			$this->flashMessage('Přihlášení k odběru proběhlo úspěšně', 'success');
		} else {
			$this->flashMessage('Odběr byl aktualizován', 'success');
		}
		$this->redirect('Homepage:default');
	}
}

