<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;
use OC\Files\Storage\File;
use OCP\Files\Storage\IStorage;

/**
 * Class FileTest
 *
 * @package Test\Files\Storage
 */
class FileTest extends NodeTest {
	/**
	 * @param $path
	 * @param IStorage|\PHPUnit_Framework_MockObject_MockObject|null $storage
	 * @return File
	 */
	protected function createTestNode($path, IStorage $storage = null) {
		if ($storage === null) {
			$storage = $this->storage;
		}
		return new File($storage, $path);
	}

	public function testGetContent() {
		$this->storage->expects($this->once())
			->method('file_get_contents')
			->with('/f1.txt')
			->willReturn('content');

		$file = $this->createTestNode('/f1.txt');
		self::assertSame('content', $file->getContent());
	}

	public function testPutContent() {
		$this->storage->expects($this->once())
			->method('file_put_contents')
			->with('/f1.txt', 'content');

		$file = $this->createTestNode('/f1.txt');
		$file->putContent('content');
	}

	public function testDelete() {
		$this->storage->expects($this->once())
			->method('unlink')
			->with('/f1.txt');

		$node = $this->createTestNode('/f1.txt');
		$node->delete();
	}

	public function testHash() {
		$this->storage->expects($this->once())
			->method('hash')
			->with('type', '/f1.txt', false)
			->willReturn('hashed');

		$file = $this->createTestNode('/f1.txt');
		self::assertSame('hashed', $file->hash('type', false));
	}

	public function testFopen() {
		$stream = \fopen('php://memory', 'wb+');
		$this->storage->expects($this->once())
			->method('fopen')
			->with('/f1.txt', 'wb+')
			->willReturn($stream);

		$file = $this->createTestNode('/f1.txt');
		self::assertSame($stream, $file->fopen('wb+'));
	}
}
