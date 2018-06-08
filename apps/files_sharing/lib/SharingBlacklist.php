<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use OCP\IConfig;
use OCP\IGroup;

/**
 * Class to handle a blacklist for sharing. The main functionality is to check if a particular group
 * has been blacklisted for sharing, which means that noone should share with that group.
 *
 * Note that this class will only handle the configuration and perform the checks against the configuration
 * This class won't prevent the sharing action by itself.
 */
class SharingBlacklist {
	/** @var IConfig */
	private $config;

	private $blacklistCache = null;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Check if the target group is blacklisted
	 * @param IGroup $group the group to check
	 * @return bool true if the group is blacklisted, false otherwise
	 */
	public function isGroupBlacklisted(IGroup $group) {
		$this->initCache();

		$groupBackend = get_class($group->getBackend());
		$groupDisplayname = $group->getDisplayName();

		if (isset($this->blacklistCache['displaynames'][$groupBackend][$groupDisplayname])) {
			return true;
		}
		return false;
	}

	/**
	 * Clear the internal cache of this class. Use this function if any of the keys used by this class is changed
	 * outside of this class, such as a direct change of the 'blacklisted_group_displaynames' in the appconfig table
	 * Note that this is an object-based cache. It won't persist for multiple HTTP requests
	 */
	public function clearCache() {
		$this->blacklistCache = null;
	}

	/**
	 * Set the list of groups to be blacklisted by displayname. The string should have the following format:
	 * {backendName}::{displayname}\n{backendName}::{displayname} ....
	 * Note that no check will be done here. It will blindly store the string
	 * @param string $displaynameString a string with the displaynames of the groups to be blacklisted
	 * using the format described above
	 */
	public function setBlacklistedGroupDisplaynames($displaynameString) {
		$this->config->setAppValue('files_sharing', 'blacklisted_group_displaynames', $displaynameString);
		$this->blacklistCache = null;  // clear the cache
	}

	/**
	 * Get the raw string of blacklisted group names as it was stored.
	 * Note that this might contain wrong information
	 * @return string the raw string as stored by the setBlacklistedGroupDisplaynames method
	 */
	public function getBlacklistedGroupDisplaynames() {
		return $this->config->getAppValue('files_sharing', 'blacklisted_group_displaynames');
	}

	private function initCache() {
		if ($this->blacklistCache === null) {

			$this->blacklistCache = [
				'displaynames' => $this->fetchBlacklistedGroupDisplaynames(),
				// blacklist by group id or other reason could be added at some point
			];
		}
	}

	private function fetchBlacklistedGroupDisplaynames() {
		$configuredBlacklist = $this->config->getAppValue('files_sharing', 'blacklisted_group_displaynames');
		$blacklistedComponents = explode("\n", $configuredBlacklist);

		$result = [];
		foreach ($blacklistedComponents as $blacklistedComponent) {
			$blacklistedComponent = \trim($blacklistedComponent);  // trim black chars

			$splittedName = explode('::', $blacklistedComponent, 2);
			if (\count($splittedName) !== 2) {
				// missing backend in the blacklisted name? Ignore
				continue;
			}

			$blacklistedBackend = $splittedName[0];
			$blacklistedDisplayname = $splittedName[1];

			if (!isset($result[$blacklistedBackend])) {
				$result[$blacklistedBackend] = [];
			}
			if (!isset($result[$blacklistedBackend][$blacklistedDisplayname])) {
				$result[$blacklistedBackend][$blacklistedDisplayname] = true;
			}
		}
		return $result;
	}
}