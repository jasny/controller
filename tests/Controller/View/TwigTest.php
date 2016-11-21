<?php

namespace Jasny\Controller\View;

use Jasny\Controller\View;
use Jasny\Controller\Session\Flash;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Jasny\Controller\TestHelper;

/**
 * @covers Jasny\Controller\View\Twig
 */
class TwigTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    protected function getControllerClass()
    {
        return View\Twig::class;
    }

    /**
     * Test creating twig environment
     */
    public function testCreateTwigEnvironment()
    {
        $controller = $this->getController([]);
        $twig = $controller->createTwigEnvironment();
        
        $this->assertInstanceOf(\Twig_Environment::class, $twig);
        $this->assertInstanceOf(\Twig_Loader_Filesystem::class, $twig->getLoader());
        $this->assertEquals([getcwd()], $twig->getLoader()->getPaths());
    }
    
    /**
     * Test intializing twig environment
     */
    public function testInitTwig()
    {
        $uri = $this->createMock(UriInterface::class);
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($uri);
        
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->once())->method('addGlobal')->with('current_url', $this->identicalTo($uri));

        $controller = $this->getController(['createTwigEnvironment']);
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        $controller->expects($this->once())->method('createTwigEnvironment')->willReturn($twig);
        
        $controller->getTwig();
    }

    /**
     * Test Jasny Twig extensions when intializing twig environment
     */
    public function testInitTwigWithJasnyExtensions()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->exactly(4))->method('addExtension')->withConsecutive(
            [$this->isInstanceOf('Jasny\Twig\DateExtension')],
            [$this->isInstanceOf('Jasny\Twig\PcreExtension')],
            [$this->isInstanceOf('Jasny\Twig\TextExtension')],
            [$this->isInstanceOf('Jasny\Twig\ArrayExtension')]
        );
        
        $controller = $this->getController(['createTwigEnvironment']);
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        $controller->expects($this->once())->method('createTwigEnvironment')->willReturn($twig);
        
        $controller->getTwig();
    }

    /**
     * Test session flash when intializing twig environment
     */
    public function testInitTwigWithSessionFlash()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $flash = $this->createMock(Flash::class);
        
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->any())->method('addGlobal')->withConsecutive([], ['flash', $flash]);
        
        $controller = $this->getController(['createTwigEnvironment', 'flash']);
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        $controller->expects($this->once())->method('flash')->willReturn($flash);
        $controller->expects($this->once())->method('createTwigEnvironment')->willReturn($twig);
        
        $controller->getTwig();
    }

    /**
     * Provide data for testing 'setViewVariable' method
     *
     * @return array
     */
    public function setViewVariableProvider()
    {
        return [
            ['foo', null],
            ['foo', 'bar'],
            ['foo', ['bar', 'zoo']],
            ['foo', (object)['a' => 'bar', 'b' => 'zoo']],
        ];  
    }
    
    /**
     * Test 'setViewVariable' method
     *
     * @dataProvider setViewVariableProvider
     */
    public function testSetViewVariable($name, $value)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        
        $controller = $this->getController(['getTwig']);
        $controller->method('getTwig')->willReturn($twig);
        
        $twig->expects($this->once())->method('addGlobal')->with($name, $value);            

        $result = $controller->setViewVariable($name, $value);

        $this->assertSame($controller, $result);
    }

    
    /**
     * Provide data for testing 'setViewFunction' method when creating functions
     *
     * @return array
     */
    public function setViewFunctionProvider()
    {
        return [
            ['test_name', function() {}],
            ['str_rot13'],
            ['obfuscate', 'str_rot13']
        ];
    }

    /**
     * Test 'setViewFunction' method for adding functions
     * @dataProvider setViewFunctionProvider
     * 
     * @param string $name 
     * @param callable $callable
     */
    public function testSetViewFunctionFunction($name, $callable = null)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        
        $controller = $this->getController(['getTwig']);
        $controller->method('getTwig')->willReturn($twig);
        
        $fn = $callable ?: $name;
        
        $twig->expects($this->once())->method('addFunction')
            ->with($this->callback(function($function) use ($name, $fn) {
                $this->assertInstanceOf(\Twig_SimpleFunction::class, $function);
                $this->assertEquals($name, $function->getName());
                $this->assertSame($fn, $function->getCallable());
                return true;
            }));
            
        $twig->expects($this->never())->method('addFilter');

        $controller->setViewFunction($name, $callable, 'function');
    }

    /**
     * Test 'setViewFunction' method for adding filters
     * @dataProvider setViewFunctionProvider
     * 
     * @param string $name 
     * @param callable $callable
     */
    public function testSetViewFunctionFilter($name, $callable = null)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        
        $controller = $this->getController(['getTwig']);
        $controller->method('getTwig')->willReturn($twig);
        
        $fn = $callable ?: $name;
        
        $twig->expects($this->once())->method('addFilter')
            ->with($this->callback(function($function) use ($name, $fn) {
                $this->assertInstanceOf(\Twig_SimpleFilter::class, $function);
                $this->assertEquals($name, $function->getName());
                $this->assertSame($fn, $function->getCallable());
                return true;
            }));
        
        $twig->expects($this->never())->method('addFunction');

        $controller->setViewFunction($name, $callable, 'filter');
    }
    
    
    public function invalidAsProvider()
    {
        return [
            ['foo', "'foo'"],
            [10, 'a integer'],
            [['filter'], 'a array']
        ];
    }
    
    /**
     * @dataProvider invalidAsProvider
     * 
     * @param mixed  $as
     * @param string $not
     */
    public function testSetViewFunctionInvalid($as, $not)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("You should create either a 'function' or 'filter', not $not");
        
        $twig = $this->createMock(\Twig_Environment::class);
        
        $controller = $this->getController(['getTwig']);
        $controller->method('getTwig')->willReturn($twig);
        
        $controller->setViewFunction('abc', null, $as);
    }
    
    
    public function assertViewVariableNameProvider()
    {
        return [
            ['setViewVariable'],
            ['setViewFunction', 'function'],
            ['setViewFunction', 'filter']
        ];
    }
    
    /**
     * @dataProvider assertViewVariableNameProvider
     * 
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string, not a stdClass object
     */
    public function testAssertViewVariableNameNonString($fn, $as = null)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        
        $controller = $this->getController(['getTwig']);
        $controller->method('getTwig')->willReturn($twig);
        
        $controller->$fn(new \stdClass(), null, $as);
    }
    
    /**
     * @dataProvider assertViewVariableNameProvider
     * 
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid name 'hello world'
     */
    public function testAssertViewVariableNameInvalid($fn, $as = null)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        
        $controller = $this->getController(['getTwig']);
        $controller->method('getTwig')->willReturn($twig);
        
        $controller->$fn('hello world', null, $as);
    }
    
    
    public function viewProvider()
    {
        return [
            ['foo', 'foo.html.twig'],
            ['foo.html.twig', 'foo.html.twig'],
            ['foo.html', 'foo.html']
        ];
    }
    
    /**
     * @dataProvider viewProvider
     * 
     * @param string $name
     * @param string $filename
     */
    public function testView($name, $filename)
    {
        $context = ['foo' => 1, 'bar' => 2, 'zoo' => ['monkey', 'lion']];
        
        $template = $this->createMock(\Twig_TemplateInterface::class);
        $template->expects($this->once())->method('render')->with($context)->willReturn('hello world');
        
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->once())->method('loadTemplate')->with($filename)->willReturn($template);
        $twig->expects($this->once())->method('getCharset')->willReturn('test-charset');
        
        $controller = $this->getController(['getTwig', 'output']);
        $controller->expects($this->atLeastOnce())->method('getTwig')->willReturn($twig);
        $controller->expects($this->once())->method('output')->with('hello world', 'text/html; charset=test-charset');
        
        $controller->view($name, $context);
    }
}
