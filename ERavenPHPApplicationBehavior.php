<?php
/**
 * ERavenPHPApplicationBehavior class file.
 *
 * @author Maxim Zemskov <nodge@yandex.ru>
 * @copyright Copyright &copy; 2010-2011 Listick.ru
 */

/**
 *
 * @package ext.raven-php
 * @since 3.0
 */
class ERavenPHPApplicationBehavior extends CBehavior {

	/**
	 *
	 * @var array Raven_Client options
	 */
	public $raven = array();

	/**
	 * @var array
	 */
	public $ignore404 = array();

	/**
	 * @var string
	 */
	public $userClass;

	/**
	 *
	 * @var array
	 */
	protected $eventId = array();

	/**
	 * @var Raven_Client Sentry stored connection
	 */
	protected $client;

	/**
	 *
	 * @param CComponent $owner
	 */
	public function attach($owner) {
		if (!class_exists('Raven_Autoloader', false)) {
			require_once 'Raven/Autoloader.php';
			Yii::registerAutoloader(array('Raven_Autoloader', 'autoload'));
		}

		if ($this->client === null) {
			$this->client = new Raven_Client($this->raven['dsn'], $this->raven);
		}

		$owner->attachEventHandler('onError', array($this, 'onError'));
		$owner->attachEventHandler('onException', array($this, 'onException'));

		parent::attach($owner);
	}

	/**
	 *
	 * @param CErrorEvent $event
	 */
	public function onError($event) {
		$options = array(
			'tags' => array(
				'type' => 'error',
			),
		);

		switch ($event->code) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$options['tags']['type'] = 'notice';
				break;

			case E_WARNING:
			case E_USER_WARNING:
				$options['tags']['type'] = 'warning';
				break;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$options['tags']['type'] = 'deprecated';
				break;

			case E_STRICT:
				$options['tags']['type'] = 'strict';
				break;
		}

		$this->setUser();
		$e = new ErrorException($event->message, 0, $event->code, $event->file, $event->line);
		$eventId = $this->capture($e, $options);
		$event->params['event_id'] = $eventId;

		$sign = $this->getEventSignature(array(
			'code' => $event->code,
			'message' => $event->message,
			'file' => $event->file,
			'line' => $event->line
		));
		$this->eventId[$sign] = $eventId;
	}

	/**
	 *
	 * @param CExceptionEvent $event
	 */
	public function onException($event) {
		if ($event->exception instanceof CHttpException) {
			if ($event->exception->statusCode === 401)
				return;
		}

		$eventId = $this->logException($event->exception);
		$event->params['event_id'] = $eventId;
	}

	/**
	 * @param Exception $e
	 * @return string|bool
	 */
	public function logException($e) {
		$options = array(
			'tags' => array(
				'type' => 'exception',
			),
		);

		if ($e instanceof CHttpException) {
			if ($e->statusCode === 404) {
				$url = Yii::app()->request->getUrl();
				foreach ($this->ignore404 as $item) {
					if (is_array($item)) {
						$item = CHtml::normalizeUrl($item);
					}
					else if (mb_strlen($item) > 0 && $item[0] !== '/') {
						$item = Yii::app()->baseUrl.'/'.$item;
					}
					if ($url === $item)
						return false;
				}
				$options['level'] = 'info';
			}

			$options['tags']['type'] = 'http.'.$e->statusCode;
		}

		$this->setUser();
		$eventId = $this->capture($e, $options);

		$sign = $this->getEventSignature(array(
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine()
		));
		$this->eventId[$sign] = $eventId;

		return $eventId;
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

	/**
	 * @param Exception $e
	 * @param array $options
	 * @return string
	 */
	protected function capture($e, $options) {
		return $this->client->getIdent($this->client->captureException($e, $options));
	}

	/**
	 *
	 * @param array $error
	 * @return string
	 */
	public function getErrorEventId($error) {
		$sign = $this->getEventSignature($error);
		return isset($this->eventId[$sign]) ? $this->eventId[$sign] : null;
	}

	/**
	 *
	 * @param array $error
	 * @return string
	 */
	protected function getEventSignature($error) {
		return md5(/*$error['code'].*/$error['message'].$error['file'].$error['line']);
	}
}