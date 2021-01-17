<?php 
declare(strict_types=1);

 if ('185.112.167.176' != $_SERVER['REMOTE_ADDR'] && '46.135.68.141' != $_SERVER['REMOTE_ADDR'] ) {
	die;
}

require __DIR__ . '/../vendor/autoload.php';

App\Bootstrap::boot()
	->createContainer()
	->getByType(Nette\Application\Application::class)
	->run();
