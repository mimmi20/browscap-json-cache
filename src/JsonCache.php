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
namespace Browscap\Cache;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use WurflCache\Adapter\AdapterInterface;

/**
 * a cache proxy to be able to use the cache adapters provided by the WurflCache package
 *
 * @category   Browscap-PHP
 *
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * @version    3.0
 *
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/browscap/browscap-php/
 */
final class JsonCache implements BrowscapCacheInterface
{
    /**
     * The cache livetime in seconds.
     *
     * @var int
     */
    public const CACHE_LIVETIME = 315360000; // ~10 years (60 * 60 * 24 * 365 * 10)

    /**
     * Path to the cache directory
     *
     * @var \WurflCache\Adapter\AdapterInterface
     */
    private $cache;

    /**
     * Detected browscap version (read from INI file)
     *
     * @var int
     */
    private $version;

    /**
     * Release date of the Browscap data (read from INI file)
     *
     * @var string
     */
    private $releaseDate;

    /**
     * Type of the Browscap data (read from INI file)
     *
     * @var string
     */
    private $type;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \WurflCache\Adapter\AdapterInterface $adapter
     * @param int                                  $updateInterval
     */
    public function __construct(AdapterInterface $adapter, $updateInterval = self::CACHE_LIVETIME)
    {
        $this->cache = $adapter;
        $this->cache->setExpiration($updateInterval);
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int|null
     */
    public function getVersion(): ?int
    {
        if (null === $this->version) {
            $success = true;

            $version = $this->getItem('browscap.version', false, $success);

            if (null !== $version && $success) {
                $this->version = (int) $version;
            }
        }

        return $this->version;
    }

    /**
     * Gets the release date of the Browscap data
     *
     * @return string
     */
    public function getReleaseDate(): string
    {
        if (null === $this->releaseDate) {
            $success = true;

            $releaseDate = $this->getItem('browscap.releaseDate', false, $success);

            if (null !== $releaseDate && $success) {
                $this->releaseDate = $releaseDate;
            }
        }

        return $this->releaseDate;
    }

    /**
     * Gets the type of the Browscap data
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        if (null === $this->type) {
            $success = true;

            $type = $this->getItem('browscap.type', false, $success);

            if (null !== $type && $success) {
                $this->type = $type;
            }
        }

        return $this->type;
    }

    /**
     * Get an item.
     *
     * @param string    $cacheId
     * @param bool      $withVersion
     * @param bool|null $success
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool &$success = null)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        if (!$this->cache->hasItem($cacheId)) {
            $success = false;

            return null;
        }

        $success = null;
        $data    = $this->cache->getItem($cacheId, $success);

        if (!$success) {
            $success = false;

            return null;
        }

        if (!property_exists($data, 'content')) {
            $success = false;

            return null;
        }

        $success = true;

        return $data->content;
    }

    /**
     * save the content into an php file
     *
     * @param string $cacheId     The cache id
     * @param mixed  $content     The content to store
     * @param bool   $withVersion
     *
     * @return bool whether the file was correctly written to the disk
     */
    public function setItem(string $cacheId, $content, bool $withVersion = true): bool
    {
        $data = new \stdClass();
        // Get the whole PHP code
        $data->content = $content;

        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        // Save and return
        return $this->cache->setItem($cacheId, $data);
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     */
    public function hasItem(string $cacheId, bool $withVersion = true): bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->hasItem($cacheId);
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     */
    public function removeItem(string $cacheId, bool $withVersion = true): bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->removeItem($cacheId);
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush(): bool
    {
        return $this->cache->flush();
    }
}
