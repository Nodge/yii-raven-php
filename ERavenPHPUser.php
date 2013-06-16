<?php

class ERavenPHPUser {

	/**
	 * @return array
	 */
	public function getAttributes() {
		$attributes = array(
			'id' => $this->getId(),
			'is_authenticated' => $this->getIsAuthenticated(),
		);

		$username = $this->getUsername();
		if (isset($username)) {
			$attributes['username'] = $username;
		}

		$email = $this->getEmail();
		if (isset($email)) {
			$attributes['email'] = $email;
		}

		return array();
	}

	/**
	 * @return string
	 */
	public function getId() {
		return Yii::app()->hasComponent('user') ? Yii::app()->user->id : 0;
	}

	/**
	 * @return bool
	 */
	public function getIsAuthenticated() {
		return Yii::app()->hasComponent('user') ? !Yii::app()->user->isGuest : false;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return null;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return null;
	}

}