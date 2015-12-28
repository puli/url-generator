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
use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Api\Type\BindingParameter;
use Puli\Discovery\Api\Type\BindingType;
use Puli\Discovery\Binding\ResourceBinding;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Puli\UrlGenerator\DiscoveryUrlGenerator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryUrlGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Discovery
     */
    private $discovery;

    /**
     * @var DiscoveryUrlGenerator
     */
    private $generator;

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
        $this->discovery = $this->getMock('Puli\Discovery\Api\Discovery');
        $this->generator = new DiscoveryUrlGenerator($this->discovery, array(
            'localhost' => '/%s',
            'example.com' => 'https://example.com/%s',
        ));
        $this->bindingType = new BindingType(DiscoveryUrlGenerator::BINDING_TYPE, array(
            new BindingParameter(DiscoveryUrlGenerator::SERVER_PARAMETER),
            new BindingParameter(DiscoveryUrlGenerator::PATH_PARAMETER),
        ));
        $this->resources = new ArrayResourceCollection(array(new GenericResource('/path')));
    }

    public function testGenerateUrl()
    {
        $binding = new ResourceBinding(
            '/path/css{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testGenerateUrlTakesFirstMatchingBinding()
    {
        $binding1 = new ResourceBinding(
            '/path/js{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE
        );
        $binding2 = new ResourceBinding(
            '/path/css{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css',
            )
        );
        $binding3 = new ResourceBinding(
            '/path/css{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding1, $binding2, $binding3));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testGenerateUrlWithDomain()
    {
        $binding = new ResourceBinding(
            '/path/css{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'example.com',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('https://example.com/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testAcceptWebPathWithLeadingSlash()
    {
        $binding = new ResourceBinding(
            '/path/css{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testAcceptWebPathWithTrailingSlash()
    {
        $binding = new ResourceBinding(
            '/path/css{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => 'css/',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testOnlyReplacePrefix()
    {
        $binding = new ResourceBinding(
            '/path{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
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
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array());

        $this->generator->generateUrl('/path/path/style.css');
    }

    /**
     * @expectedException \Puli\UrlGenerator\Api\CannotGenerateUrlException
     * @expectedExceptionMessage foobar
     */
    public function testFailIfServerNotFound()
    {
        $binding = new ResourceBinding(
            '/path{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'foobar',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->generator->generateUrl('/path/path/style.css');
    }

    /**
     * @covers \Puli\UrlGenerator\DiscoveryUrlGenerator::generateUrl
     */
    public function testGenerateUrlRelativeUrl()
    {
        $binding = new ResourceBinding(
            '/path{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('path/style.css', $this->generator->generateUrl('/path/path/style.css', 'http://example.com/css'));
    }

    /**
     * @expectedException \Puli\UrlGenerator\Api\CannotGenerateUrlException
     * @expectedExceptionMessage Cannot generate URL for "/path/path/style.css" to current url "/".
     * @covers \Puli\UrlGenerator\DiscoveryUrlGenerator::generateUrl
     */
    public function testMakeRelativeFails()
    {
        $binding = new ResourceBinding(
            '/path{,/**/*}',
            DiscoveryUrlGenerator::BINDING_TYPE,
            array(
                DiscoveryUrlGenerator::SERVER_PARAMETER => 'localhost',
                DiscoveryUrlGenerator::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findBindings')
            ->with(DiscoveryUrlGenerator::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->generator->generateUrl('/path/path/style.css', '/');
    }
}
