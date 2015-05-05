<?php

/*
 * This file is part of the puli/url-generator package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\UrlGenerator\Api;

use Puli\Discovery\Api\ResourceDiscovery;

/**
 * A factory for resource URL generators.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface UrlGeneratorFactory
{
    /**
     * Creates the URL generator.
     *
     * @param ResourceDiscovery $discovery The resource discovery to read from.
     *
     * @return UrlGenerator The created URL generator.
     */
    public function createUrlGenerator(ResourceDiscovery $discovery);
}
