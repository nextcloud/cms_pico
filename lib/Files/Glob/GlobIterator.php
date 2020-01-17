<?php

namespace OCA\CMSPico\Files\Glob;

use OCA\CMSPico\Files\FileInterface;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Files\NodeInterface;
use OCP\Files\GenericFileException;
use OCP\Files\NotPermittedException;

class GlobIterator implements \Iterator
{
	/** @var FolderInterface */
	private $folder;

	/** @var GlobPattern */
	private $pattern;

	/** @var FolderInterface[] */
	private $folders;

	/** @var FolderInterface */
	private $current;

	/** @var int */
	private $depth;

	/** @var FileInterface */
	private $currentValue;

	/** @var int */
	private $currentKey;

	/**
	 * GlobIterator constructor.
	 *
	 * @param FolderInterface $folder
	 * @param string          $pattern
	 */
	public function __construct(FolderInterface $folder, string $pattern)
	{
		$this->folder = $folder;
		$this->pattern = new GlobPattern($pattern);

		$this->init();
	}

	/**
	 * @return void
	 */
	private function init(): void
	{
		$this->folders = [ $this->folder ];
		$this->current = $this->folder;
		$this->depth = 0;

		$this->currentValue = null;
		$this->currentKey = -1;
	}

	/**
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	public function rewind(): void
	{
		$this->init();

		$this->current->rewind();
	}

	/**
	 * @return void
	 */
	public function next(): void
	{
		$this->current->next();
	}

	/**
	 * @return bool
	 */
	public function valid(): bool
	{
		do {
			if ($this->current->valid()) {
				/** @var NodeInterface $file */
				$file = $this->current->current();

				if (!$this->pattern->compare($this->depth, $file->getName())) {
					$this->next();
					continue;
				}

				if ($file->isFolder()) {
					/** @var FolderInterface $file */
					$this->descend($file);
					continue;
				}

				/** @var FileInterface $file */
				$this->set($file);
				return true;
			} elseif ($this->depth > 0) {
				$this->ascend();
				continue;
			}

			return false;
		} while (true);
	}

	/**
	 * @return FileInterface
	 */
	public function current(): FileInterface
	{
		return $this->currentValue;
	}

	/**
	 * @return int
	 */
	public function key(): int
	{
		return $this->currentKey;
	}

	/**
	 * @param FolderInterface $folder
	 */
	private function descend(FolderInterface $folder): void
	{
		$this->folders[] = $folder;
		$this->depth++;

		$this->current = $this->folders[$this->depth];
		$this->current->rewind();
	}

	/**
	 * @return void
	 */
	private function ascend(): void
	{
		array_pop($this->folders);
		$this->depth--;

		$this->current = $this->folders[$this->depth];
		$this->current->next();
	}

	/**
	 * @param FileInterface $file
	 */
	private function set(FileInterface $file): void
	{
		$this->currentValue = $file;
		$this->currentKey++;
	}
}
