<?php
/**
 * This file is part of the browscap-json-cache package.
 *
 * (c) Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
/**
 * Copyright (c) 2013-2014 Thomas Müller
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   WurflCache
 *
 * @copyright  2013-2014 Thomas Müller
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/WurflCache/
 */
namespace Browscap\Cache\Adapter;

use Wurfl\WurflConstants;
use WurflCache\Adapter\AbstractAdapter;
use WurflCache\Adapter\Exception;
use WurflCache\Utils\FileUtils;

/**
 * Adapter to use Files for caching
 *
 * @category   WurflCache
 *
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  2013-2014 Thomas Müller
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/WurflCache/
 */
class JsonFile extends AbstractAdapter
{
    /**
     * @var string
     */
    public const DIR = 'dir';

    /**
     * @var array
     */
    protected $defaultParams = [
        self::DIR          => '/tmp',
        'namespace'        => 'browscap-json',
        'cacheExpiration'  => 0,
        'readonly'         => false,
        'cacheVersion'     => WurflConstants::API_NAMESPACE,
    ];

    /**
     * @var string
     */
    private $root;

    /**
     * @var bool
     */
    private $readonly = false;

    /**
     * Get an item.
     *
     * @param string $cacheId
     * @param bool   $success
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem($cacheId, &$success = null)
    {
        $success = false;

        if (!$this->hasItem($cacheId)) {
            return null;
        }

        $path = $this->keyPath($cacheId);

        /** @var mixed $value */
        $value = json_decode(FileUtils::read($path));
        if (null === $value) {
            return null;
        }

        $success = true;

        return $value;
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     *
     * @return bool
     */
    public function hasItem($cacheId): bool
    {
        $path = $this->keyPath($cacheId);

        return FileUtils::exists($path);
    }

    /**
     * Store an item.
     *
     * @param string $cacheId
     * @param mixed  $value
     *
     * @return bool
     */
    public function setItem($cacheId, $value): bool
    {
        $path = $this->keyPath($cacheId);

        return FileUtils::write(
            $path,
            json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     *
     * @return bool
     */
    public function removeItem($cacheId): bool
    {
        $path = $this->keyPath($cacheId);

        return unlink($path);
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush(): bool
    {
        return FileUtils::rmdir($this->root);
    }

    /**
     * @param array $params
     *
     * @throws \WurflCache\Adapter\Exception
     *
     * @return void
     */
    protected function toFields(array $params): void
    {
        if (isset($params['namespace'])) {
            $this->setNamespace($params['namespace']);
        }

        if (isset($params['cacheExpiration'])) {
            $this->setExpiration($params['cacheExpiration']);
        }

        if (isset($params['cacheVersion'])) {
            $this->setCacheVersion($params['cacheVersion']);
        }

        $this->root            = $params[self::DIR];
        $this->cacheExpiration = $params['cacheExpiration'];
        $this->readonly        = ('true' === $params['readonly'] || true === $params['readonly']);

        $this->createRootDirIfNotExist();
    }

    /**
     * @throws \WurflCache\Adapter\Exception
     *
     * @return void
     */
    private function createRootDirIfNotExist(): void
    {
        if (!isset($this->root)) {
            throw new Exception(
                'You have to provide a path to read/store the browscap cache file',
                Exception::CACHE_DIR_MISSING
            );
        }

        // Is the cache dir really the directory or is it directly the file?
        if (is_file($this->root)) {
            $this->root = dirname($this->root);
        } elseif (!is_dir($this->root)) {
            @mkdir(
                $this->root,
                0777,
                true
            );

            if (!is_dir($this->root)) {
                throw new Exception(
                    'The file storage directory does not exist and could not be created. '
                    . 'Please make sure the directory is writeable: "' . $this->root . '"'
                );
            }
        }

        if (!is_readable($this->root)) {
            throw new Exception(
                'Its not possible to read from the given cache path "' . $this->root . '"',
                Exception::CACHE_DIR_NOT_READABLE
            );
        }

        if (!$this->readonly && !is_writable($this->root)) {
            throw new Exception(
                'Its not possible to write to the given cache path "' . $this->root . '"',
                Exception::CACHE_DIR_NOT_WRITABLE
            );
        }
    }

    /**
     * @param string $cacheId
     *
     * @return string
     */
    private function keyPath(string $cacheId): string
    {
        return FileUtils::join([$this->root, $cacheId . '.json']);
    }
}
