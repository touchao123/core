<?php
/**
 * @author Piotr Mrowczynski <piotr@owncloud.com>
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

namespace OC\Files\ObjectStore;

use OC\Cache\CappedMemoryCache;
use OC\Files\Cache\CacheEntry;
use OCP\Files\Cache\ICache;

/**
 * Metadata cache for objectstorage file objects. This is objectstore storage filecache handler,
 * as in objectstore implementation filecache is storage source of truth.
 */
class ObjectStoreMetadata extends \OC\Files\Cache\Cache {

	/**
	 * @param CappedMemoryCache
	 */
	private $metaDataCache = null;

	/**
	 * @param \OC\Files\Storage\Storage|string $storage
	 */
	public function __construct($storage) {
		// Objectstore metadata cache can be safely used since in objectstore filecache
		// is source of truth. However, to ensure high level of filecache -> storage cache consistency,
		// only one object entry is being cached at the time.
		// This sort of caching ensures that repetitive calls to filecache
		// for the same object are consistent across application logic
		if ($this->metaDataCache === null) {
			$this->metaDataCache = new CappedMemoryCache(1);
		}
		parent::__construct($storage);
	}

	/**
	 * @inheritdoc
	 */
	public function get($file) {
		$key = $this->getNumericStorageId().'-get-'.$file;
		if ($this->metaDataCache->hasKey($key)) {
			$metadata = $this->metaDataCache->get($key);
			return new CacheEntry($metadata);
		}

		/** @var CacheEntry $data */
		$data = parent::get($file);

		// Cache only single object entries
		if ($data && $data->getMimeType() !== 'httpd/unix-directory') {
			// Cache only repetitive calls for the same object metadata.
			// If get for another $file is issued, cache is being cleared
			$this->metaDataCache->clear();
			$metadata = $data->getData();
			$this->metaDataCache->set($key, $metadata);
		}
		return $data;
	}

	/**
	 * @inheritdoc
	 */
	public function getId($file) {
		$this->metaDataCache->clear();
		return parent::getId($file);
	}

	/**
	 * @inheritdoc
	 */
	public function getAll() {
		$this->metaDataCache->clear();
		return parent::getAll();
	}

	/**
	 * @inheritdoc
	 */
	public function getPathById($id) {
		$this->metaDataCache->clear();
		return parent::getPathById($id);
	}

	/**
	 * @inheritdoc
	 */
	public function getFolderContentsById($fileId) {
		$this->metaDataCache->clear();
		return parent::getFolderContentsById($fileId);
	}

	/**
	 * @inheritdoc
	 */
	public function search($pattern) {
		$this->metaDataCache->clear();
		return parent::search($pattern);
	}

	/**
	 * @inheritdoc
	 */
	public function searchByMime($mimetype) {
		$this->metaDataCache->clear();
		return parent::searchByMime($mimetype);

	}

	/**
	 * @inheritdoc
	 */
	public function searchByTag($tag, $userId) {
		$this->metaDataCache->clear();
		return parent::searchByTag($tag, $userId);
	}

	/**
	 * @inheritdoc
	 */
	public function insert($file, array $data) {
		$this->metaDataCache->clear();
		return parent::insert($file, $data);
	}

	/**
	 * @inheritdoc
	 */
	public function update($id, array $data) {
		$this->metaDataCache->clear();
		parent::update($id, $data);
	}

	/**
	 * @inheritdoc
	 */
	public function remove($file) {
		$this->metaDataCache->clear();
		parent::remove($file);
	}

	/**
	 * @inheritdoc
	 */
	public function move($source, $target) {
		$this->metaDataCache->clear();
		parent::move($source, $target);
	}

	/**
	 * @inheritdoc
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$this->metaDataCache->clear();
		parent::moveFromCache($sourceCache, $sourcePath, $targetPath);
	}

	/**
	 * @inheritdoc
	 */
	public function clear() {
		$this->metaDataCache->clear();
		parent::clear();
	}
}
