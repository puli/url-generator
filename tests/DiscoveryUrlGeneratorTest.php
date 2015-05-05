<?php

/*
 * This file is part of the puli/url-generator package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\UrlGenerator\Tests;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Discovery\Api\Binding\BindingParameter;
use Puli\Discovery\Api\Binding\BindingType;
use Puli\Discovery\Api\ResourceDiscovery;
use Puli\Discovery\Binding\EagerBinding;
use Puli\Manager\Api\Package\Package;
use Puli\Manager\Api\Package\PackageFile;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Puli\UrlGenerator\DiscoveryUrlGenerator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryUrlGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ResourceDiscovery
     */
    private $discovery;

    /**
     * @var DiscoveryUrlGenerator
     */
    private $generator;

    /**
     * @var Package
     */
    private $package;

    /**
     * @var BindingType
     */
    private $bindingType;

    /**
     * @var ResourceCollection
     */
    private $resources;

    protected function setUp()
    {
        $this->discovery = $this->getMock('Puli\Discovery\Api\ResourceDiscovery');
        $this->generator = new DiscoveryUrlGenerator($this->discovery, array(
            'localhost' => '/%s',
            'example.com' => 'https://example.com/%s',
        ));
        $this->package = new Package(new PackageFile('vendor/package'), '/path');
        $this->bindingType = new BindingType(DiscoveryUrlGenerator::BINDING_TYPE, array(
            new BindingParameter(DiscoveryUrlGenerator::SERVER_PARAMETER),
            new BindingParameter(DiscoveryUrlGenerator::PATH_PARAMETER),
        ));
        $this->resources = new ArrayResourceCollection(array(new GenericResource('/path')));
    }

    public function testGenerateUrl()
    {
        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testGenerateUrlWithDomain()
    {
        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'example.com',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('https://example.com/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testAcceptWebPathWithLeadingSlash()
    {
        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testAcceptWebPathWithTrailingSlash()
    {
        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css/',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testOnlyReplacePrefix()
    {
        $binding = new EagerBinding(
            '/path{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/path/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/path/style.css', $this->generator->generateUrl('/path/path/style.css'));
    }

    /**
     * @expectedException \Puli\UrlGenerator\Api\CannotGenerateUrlException
     * @expectedExceptionMessage /path/path/style.css
     */
    public function testFailIfResourceNotMapped()
    {
        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/path/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array());

        $this->generator->generateUrl('/path/path/style.css');
    }

    /**
     * @expectedException \Puli\UrlGenerator\Api\CannotGenerateUrlException
     * @expectedExceptionMessage foobar
     */
    public function testFailIfServerNotFound()
    {
        $binding = new EagerBinding(
            '/path{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'foobar',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/path/style.css', DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->generator->generateUrl('/path/path/style.css');
    }
}
