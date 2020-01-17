<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2019, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace OCA\CMSPico\Files\Glob;

class GlobPattern
{
	/** @var int */
	protected const TYPE_NONE = 0;

	/** @var int */
	protected const TYPE_STATIC = 1;

	/** @var int */
	protected const TYPE_REGEX = 2;

	/** @var string */
	protected $delimiter = '~';

	/** @var string[] */
	protected $functionChars = [ '*', '?', '[' ];

	/** @var string[] */
	protected $specialChars = [ '*', '?', '[', ']', '-', '\\' ];

	/** @var string[] */
	protected $escapeChars = [ '.', '+', '^', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '#', '~' ];

	/** @var string */
	protected $pattern;

	/** @var int */
	protected $patternLength;

	/** @var int */
	protected $patternPos = 0;

	/** @var string */
	protected $current = '';

	/** @var array */
	protected $context = [];

	/** @var array */
	protected $components = [];

	/**
	 * GlobPattern constructor.
	 *
	 * @param string $pattern
	 */
	public function __construct(string $pattern)
	{
		$this->pattern = $pattern;
		$this->patternLength = strlen($pattern);
	}

	/**
	 * @param int    $depth
	 * @param string $fileName
	 *
	 * @return bool
	 */
	public function compare(int $depth, string $fileName): bool
	{
		/** @var int $componentType */
		/** @var string $componentPattern */
		[ $componentType, $componentPattern ] = $this->getComponent($depth);

		if ($componentType === self::TYPE_STATIC) {
			return ($fileName === $componentPattern);
		} elseif ($componentType === self::TYPE_REGEX) {
			return (bool) preg_match($componentPattern, $fileName);
		}

		return false;
	}

	/**
	 * @param int $depth
	 *
	 * @return array
	 */
	private function getComponent(int $depth): array
	{
		while (!isset($this->components[$depth])) {
			if ($this->patternPos === $this->patternLength) {
				return [ self::TYPE_NONE, '' ];
			}

			$this->evaluateComponent();
		}

		return $this->components[$depth];
	}

	/**
	 * @return void
	 */
	private function evaluateComponent(): void
	{
		$this->resetComponent();

		for (; $this->patternPos < $this->patternLength; $this->patternPos++) {
			$char = $this->pattern[$this->patternPos];

			if ($char === '/') {
				$this->patternPos++;
				break;
			}

			if ($this->context['isStatic']) {
				if (!in_array($char, $this->functionChars, true)) {
					$this->current .= $char;
					continue;
				}

				$this->current = preg_quote($this->current, $this->delimiter);
				$this->context['isStatic'] = false;
			}

			$this->evaluateComponentChar($char);
		}

		$this->commitComponent();
	}

	/**
	 * @param string $char
	 */
	private function evaluateComponentChar(string $char): void
	{
		$i = &$this->patternPos;

		switch ($char) {
			case '*':
				$this->current .= $this->context['inGroup'] ? '\\*' : '[^/]*';
				break;

			case '?':
				$this->current .= $this->context['inGroup'] ? '\\?' : '.';
				break;

			case '[':
				if ($this->context['inGroup']) {
					$this->current .= '\\[';
					break;
				}

				$this->current .= '[';

				$this->context['inGroup'] = true;
				if (isset($this->pattern[$i + 1]) && ($this->pattern[$i + 1] === '!')) {
					$this->current .= '^';
					$i++;
				}
				break;

			case ']':
				$this->current .= $this->context['inGroup'] ? ']' : '\\]';

				$this->context['inGroup'] = false;
				break;

			case '-':
				$this->current .= $this->context['inGroup'] ? '-' : '\\-';
				break;

			case '\\':
				if (isset($this->pattern[$i + 1]) && in_array($this->pattern[$i + 1], $this->specialChars, true)) {
					$this->current .= '\\' . $this->pattern[$i + 1];
					$i++;
				} else {
					$this->current .= '\\\\';
				}
				break;

			default:
				$this->current .= in_array($char, $this->escapeChars, true) ? '\\' . $char : $char;
				break;
		}
	}

	/**
	 * @return void
	 */
	private function commitComponent(): void
	{
		if ($this->context['inGroup']) {
			throw new \InvalidArgumentException('Invalid glob: Missing "]": ' . $this->pattern);
		}

		if ($this->context['isStatic']) {
			$this->components[] = [ self::TYPE_STATIC, $this->current ];
		} else {
			$regex = $this->delimiter . '^' . $this->current . '$' . $this->delimiter;
			$this->components[] = [ self::TYPE_REGEX, $regex ];
		}
	}

	/**
	 * @return void
	 */
	private function resetComponent(): void
	{
		$this->current = '';
		$this->context = [
			'isStatic' => true,
			'inGroup' => false
		];
	}
}
