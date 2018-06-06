<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 */

namespace OC\Lock\Persistent;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class LockMapper extends Mapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'persistent_locks', null);
	}

	/**
	 * @param int $storageId
	 * @param string $internalPath
	 * @param bool $returnChildLocks
	 * @return Lock[]
	 */
	public function getLocksByPath($storageId, $internalPath, $returnChildLocks) {
		$query = $this->db->getQueryBuilder();
		$pathPattern = $this->db->escapeLikeParameter($internalPath) . '%';

		//
		// TODO: handle locks on parents
		//
		$query->select(['id', 'owner', 'timeout', 'created_at', 'token', 'scope', 'depth', 'file_id', 'path'])
			->from($this->getTableName(), 'l')
			->join('l', 'filecache', 'f', $query->expr()->eq('l.file_id', 'f.fileid'))
			->where($query->expr()->eq('storage', $query->createNamedParameter($storageId)))
			->andWhere($query->expr()->gt('created_at', $query->createFunction('(' . $query->createNamedParameter(\time()) . ' - `timeout`)')));

		if ($returnChildLocks) {
			$query->andWhere($query->expr()->like('f.path', $query->createNamedParameter($pathPattern)));
		} else {
			$query->andWhere($query->expr()->eq('f.path', $query->createNamedParameter($internalPath)));
		}

		// We need to check locks for every part in the uri.
		$uriParts = \explode('/', $internalPath);

		// We already covered the last part of the uri
		\array_pop($uriParts);

		$currentPath = '';
		foreach ($uriParts as $part) {
			if ($currentPath) {
				$currentPath .= '/';
			}
			$currentPath .= $part;
			$query->orWhere(
				$query->expr()->andX(
					$query->expr()->neq('depth', $query->createNamedParameter(0)),
					$query->expr()->eq('path', $query->createNamedParameter($currentPath))
				)
			);
		}

		return $this->findEntities($query->getSQL(), $query->getParameters());
	}

	public function deleteByFileIdAndToken($fileId, $token) {
		$query = $this->db->getQueryBuilder();

		$rowCount = $query->delete($this->getTableName())
			->where($query->expr()->eq('file_id', $query->createNamedParameter($fileId)))
			->andWhere($query->expr()->eq('token', $query->createNamedParameter($token)))
			->execute();

		return $rowCount === 1;
	}
}
