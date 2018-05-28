<?php

namespace OCP\Authentication;

use OC\Authentication\Exceptions\AccountCheckException;
use OCP\IUser;

/**
 * Interface IAccountModule
 *
 * @package OCP\Authentication
 * @since 10.0.9
 */
interface IAccountModule {

	/**
	 *
	 * @since 10.0.9
	 *
	 * @param IUser $user
	 * @throws AccountCheckException
	 */
	public function check(IUser $user);
}
