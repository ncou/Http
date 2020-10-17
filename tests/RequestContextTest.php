<?php

declare(strict_types=1);

namespace Chiron\Http\Test;

use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Nyholm\Psr7\ServerRequest;
use Chiron\Container\Container;
use Chiron\Http\RequestContext;
use Chiron\Http\ServerBag;
use Chiron\Http\ParameterBag;
use Chiron\Http\HeaderBag;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\UploadedFile;

// TODO : créer une méthode privée createRequest() qui se charge de retourner un ServerRequestInterface !!!!
class RequestContextTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var RequestContext
     */
    private $context;

    public function setUp(): void
    {
        $this->container = new Container();
        $this->context = new RequestContext($this->container);
    }

    public function testGetBag(): void
    {
        $request = new ServerRequest('GET', 'http://domain.com/hello-world');
        $this->container->bind(ServerRequestInterface::class, $request);

        $this->assertInstanceOf(ServerBag::class, $this->context->server);
        $this->assertInstanceOf(ParameterBag::class, $this->context->attributes);
        $this->assertInstanceOf(ParameterBag::class, $this->context->data);
        $this->assertInstanceOf(ParameterBag::class, $this->context->cookies);
        $this->assertInstanceOf(ParameterBag::class, $this->context->query);
        $this->assertInstanceOf(ParameterBag::class, $this->context->files);
        $this->assertInstanceOf(HeaderBag::class, $this->context->headers);

        $this->assertInstanceOf(ServerBag::class, $this->context->bag('server'));
        $this->assertInstanceOf(ParameterBag::class, $this->context->bag('attributes'));
        $this->assertInstanceOf(ParameterBag::class, $this->context->bag('data'));
        $this->assertInstanceOf(ParameterBag::class, $this->context->bag('cookies'));
        $this->assertInstanceOf(ParameterBag::class, $this->context->bag('query'));
        $this->assertInstanceOf(ParameterBag::class, $this->context->bag('files'));
        $this->assertInstanceOf(HeaderBag::class, $this->context->bag('headers'));
    }

    public function testWrongBag(): void
    {
        $request = new ServerRequest('GET', 'http://domain.com/hello-world');

        $this->container->bind(ServerRequestInterface::class, $request);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Undefined input bag 'invalid'");

        $this->context->bag('invalid');
    }

    public function testWrongBagShortcut(): void
    {
        $request = new ServerRequest('GET', 'http://domain.com/hello-world');

        $this->container->bind(ServerRequestInterface::class, $request);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Undefined input bag 'invalid'");

        $this->context->invalid;
    }


    public function testParameterBagShortcuts(): void
    {
        $request = new ServerRequest('GET', 'http://domain.com/hello-world');
        $request = $request->withParsedBody([
            'user' => 'foobar',
            'name'  => 'xx'
        ])->withQueryParams([
            'name' => 'value',
            'key'  => 'hi'
        ])->withAttribute('attr', 'value')->withCookieParams([
            'cookie' => 'cookie-value'
        ]);

        $this->container->bind(ServerRequestInterface::class, $request);

        $this->assertSame('foobar', $this->context->data('user'));
        $this->assertSame('foobar', $this->context->post('user'));

        $this->assertSame('value', $this->context->query('name'));
        $this->assertSame('hi', $this->context->query('key'));

        $this->assertSame('xx', $this->context->input('name'));
        $this->assertSame('hi', $this->context->input('key'));
        $this->assertSame('value', $this->context->attribute('attr'));

        $this->assertSame('cookie-value', $this->context->cookie('cookie'));
    }

    public function testFileBagShortcut(): void
    {
        $file = new UploadedFile(
                fopen(__FILE__, 'r'),
                filesize(__FILE__),
                0,
                __FILE__
            );

        $request = new ServerRequest('GET', 'http://domain.com/hello-world');
        $request = $request->withUploadedFiles([
            'my_file' => $file
        ]);

        $this->container->bind(ServerRequestInterface::class, $request);

        $this->assertSame($file, $this->context->file('my_file'));
        $this->assertNull($this->context->file('non_existing_file'));
    }
}
