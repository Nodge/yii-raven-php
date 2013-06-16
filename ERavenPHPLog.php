<?php

/**
 * ERavenPHPLog class file.
 *
 * @author Rolies Deby <rolies106@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2012 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * ERavenPHPLog records log messages to sentry server.
 *
 * @author Rolies Deby <rolies106@gmail.com>
 * @version $Id: CFileLogRoute.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.logging
 * @since 1.0
 */
class ERavenPHPLog extends CLogRoute {

	/**
	 *
	 * @var array Raven_Client options
	 */
	public $raven = array();

	/**
	 * @var string
	 */
	public $userClass;

	/**
	 * @var Raven_Client Sentry stored connection
	 */
	protected $client;

	/**
	 * Initializes the connection.
	 */
	public function init() {
		parent::init();

		if (!class_exists('Raven_Autoloader', false)) {
			require_once 'Raven/Autoloader.php';
			Yii::registerAutoloader(array('Raven_Autoloader', 'autoload'));
		}

		if ($this->client === null) {
			$this->client = new Raven_Client($this->raven['dsn'], $this->raven);
		}
	}

	/**
	 * Send log messages to Sentry.
	 * @param array $logs list of log messages
	 * @throws CException|Exception
	 */
	protected function processLogs($logs) {
		$this->setUser();

		foreach ($logs as $log) {
			$options = array();

			try {
				$event_id = Yii::app()->getErrorEventId(Yii::app()->errorHandler->error);
				if (isset($event_id))
					continue;
			}
			catch (CException $e) {
				if (strpos($e->getMessage(), 'getErrorEventId') === false)
					throw $e;
			}

			if (!isset($this->raven['level'])) {
				switch ($log[1]) {
					case 'error':
						$options['level'] = Raven_Client::ERROR;
						break;

					case 'warning':
						$options['level'] = Raven_Client::WARNING;
						break;

					case 'info':
						$options['level'] = Raven_Client::INFO;
						break;

					case 'trace':
						$options['level'] = Raven_Client::DEBUG;
						break;
				}
			}

			$format = explode("\n", $log[0]);
			$message = array_shift($format);

			foreach ($format as $i => $msg) {
				$options['extra']['message_'.($i+1)] = $msg;
			}

			$this->client->captureMessage($message, $options);
		}
	}

	/**
	 *
	 */
	protected function setUser() {
		if (isset($this->userClass)) {
			if ($this->userClass === false)
				return;
			$class = Yii::import($this->userClass, true);
		}
		else {
			$class = 'ERavenPHPUser';
			require_once 'ERavenPHPUser.php';
		}

		/** @var $user ERavenPHPUser */
		$user = new $class();
		$this->client->setUser($user->getAttributes());
	}

}