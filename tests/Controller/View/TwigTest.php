<?php

namespace Jasny\Controller\View;

use Jasny\Controller\View\Twig;
use Jasny\Controller\Session\Flash;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers Jasny\Controller\View\Twig
 */
class TwigTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test creating twig environment
     */
    public function testGetTwig()
    {
        $view = $this->getView(['getTwigLoader', 'getTwigEnvironment', 'createTwigFunction', 'createTwigFilter', 'setViewExtension', 'setViewVariable']);
        list($request, $response) = $this->getRequests();
        list($loader, $env) = $this->getTwigObjects();

        $view->expects($this->once())->method('getTwigLoader')->will($this->returnValue($loader));
        $view->expects($this->once())->method('getTwigEnvironment')->with($this->equalTo($loader))->will($this->returnValue($env));
        $view->expects($this->exactly(4))->method('setViewExtension')->with($this->callback(function($ext) {
            return in_array(get_class($ext), ['Jasny\Twig\DateExtension', 'Jasny\Twig\PcreExtension', 'Jasny\Twig\TextExtension', 'Jasny\Twig\ArrayExtension'], true);
        }))->will($this->returnSelf());

        $path = '/test/request/path';
        $uri = $this->createMock(UriInterface::class);

        $view->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));
        $uri->expects($this->once())->method('getPath')->will($this->returnValue($path));
        $view->expects($this->exactly(2))->method('setViewVariable')->withConsecutive(
            [$this->equalTo('current_url'), $this->equalTo($path)],
            [$this->equalTo('flash'), $this->callback(function($flash) {
                return $flash instanceof Flash && empty($flash->get());
            })]
        );

        $result = $view->getTwig();
        $resultSaved = $view->getTwig();

        $this->assertEquals($env, $result, "Twig environment should be returned");
        $this->assertEquals($env, $resultSaved, "Saved twig environment should be returned");
    }

    /**
     * Test getting twig loader and environment
     */
    public function testGetTwigEnvironment()
    {
        $view = $this->getView();
        $loader = $view->getTwigLoader();
        $env = $view->getTwigEnvironment($loader);

        $this->assertInstanceOf(Twig_Environment::class, $env);
        $this->assertInstanceOf(Twig_Loader_Filesystem::class, $loader);
        $this->assertEquals([getcwd()], $loader->getPaths(), "Twig loader should be configured for current dir");
    }

    /**
     * Test 'view' function
     *
     * @dataProvider viewProvider
     * @param string $tmplName      Name of template to render
     * @param string $tmplNameFull  Name of template with extension
     * @param array $context        Variables to render
     * @param string $charset       Twig sharset
     * @param string $html          Rendered template
     */
    public function testView($tmplName, $tmplNameFull, $context, $charset, $html)
    {
        $view = $this->getView(['getTwig']);
        list($request, $response) = $this->getRequests();       
        list($loader, $env, $tmpl) = $this->getTwigObjects(); 

        $stream = $this->createMock(StreamInterface::class);

        $view->expects($this->once())->method('getTwig')->will($this->returnValue($env));
        $view->expects($this->once())->method('getResponse')->will($this->returnValue($response));
        $env->expects($this->once())->method('loadTemplate')->with($this->equalTo($tmplNameFull))->will($this->returnValue($tmpl));        
        $env->expects($this->once())->method('getCharset')->will($this->returnValue($charset));        
        $response->expects($this->once())->method('withHeader')->with($this->equalTo('Content-Type'), $this->equalTo('text/html; charset=' . $charset ))->will($this->returnSelf());
        $response->expects($this->once())->method('getBody')->will($this->returnValue($stream));
        $tmpl->expects($this->once())->method('render')->with($this->equalTo($context))->will($this->returnValue($html));
        $stream->expects($this->once())->method('write')->with($this->equalTo($html));

        $result = $view->view($tmplName, $context);

        $this->assertEquals($response, $result, "Response should be returned");
    }

    /**
     * Provide data for testing view method
     *
     * @return array
     */
    public function viewProvider()
    {
        return [
            ['test-template', 'test-template.html.twig', ['test' => 'value'], 'test-charset', 'rendered template'],
            ['test-template.html.twig', 'test-template.html.twig', ['test' => 'value'], 'test-charset', 'rendered template'],
        ];
    }

    /**
     * Test creating twig function
     *
     * @dataProvider createTwigFunctionFilterProvider
     * @param string $class         Twig function or filter class
     * @param string $createMethod 
     * @param string $name          Created function or filter name
     * @param callable $function 
     */
    public function testCreateTwigFunctionFilter($class, $createMethod, $name, $function)
    {
        if (!$name) $this->expectException(InvalidArgumentException::class);

        $view = $this->getView();
        $result = $view->createTwigFunction($name, $function);
        $callback = $result->getCallable();

        $this->assertInstanceOf(Twig_SimpleFunction::class, $result, "Result must be an instance of 'Twig_SimpleFunction'");
        $this->assertEquals($name, $result->getName(), "Function name is not set correctly");

        if (!$function) {
            $this->assertEquals($name, $callback);
        } else {
            $this->assertEquals(call_user_func($function), call_user_func($callback), "Function body was not set correctly");
        }
    }

    /**
     * Provide data for testing creating functions and filter for twig
     *
     * @return array
     */
    public function createTwigFunctionFilterProvider()
    {
        return [
            [Twig_SimpleFunction::class, 'createTwigFunction', 'test_name', function() {return 'success_call';}],
            [Twig_SimpleFunction::class, 'createTwigFunction', 'test_name', null],
            [Twig_SimpleFilter::class, 'createTwigFilter', 'test_name', function() {return 'success_call';}],
            [Twig_SimpleFilter::class, 'createTwigFilter', 'test_name', null],
            [Twig_SimpleFunction::class, 'createTwigFunction', '', function() {return 'success_call';}],
            [Twig_SimpleFilter::class, 'createTwigFilter', '', function() {return 'success_call';}],
        ];
    }

    /**
     * Test 'setViewFunction' method
     *
     * @dataProvider setViewFunctionFunctionProvider
     * @param string $name 
     * @param callable $callable
     * @param string $type 
     * @param boolean $positive
     */
    public function testSetViewFunctionFunction($name, $callable, $type, $positive)
    {
        $view = $this->getView(['getTwig', 'createTwigFunction', 'createTwigFilter']);
        $twig = $this->createMock(Twig_Environment::class);
        $function = $this->createMock(Twig_SimpleFunction::class);

        if ($positive) {            
            $view->expects($this->once())->method('getTwig')->will($this->returnValue($twig));
            $view->expects($this->once())->method('createTwigFunction')->with($this->equalTo($name), $this->equalTo($callable))->will($this->returnValue($function));            
            $view->expects($this->never())->method('createTwigFilter');            
            $twig->expects($this->once())->method('addFunction')->with($this->equalTo($function));
        } else {
            $this->expectException(InvalidArgumentException::class);
        }

        $result = $view->setViewFunction($name, $callable, $type);

        $this->assertEquals($view, $result, "Method should return \$this");
    }

    /**
     * Provide data for testing 'setViewFunction' method when creating functions
     *
     * @return array
     */
    public function setViewFunctionFunctionProvider()
    {
        return [
            ['test_name', function() {}, 'function', true],
            ['test_name', function() {}, 'not_function_or_filter', false]
        ];
    }

    /**
     * Test 'setViewFunction' method
     *
     * @dataProvider setViewFunctionFilterProvider
     * @param string $name 
     * @param callable $callable
     */
    public function testSetViewFunctionFilter($name, $callable)
    {
        $view = $this->getView(['getTwig', 'createTwigFunction', 'createTwigFilter']);
        $twig = $this->createMock(Twig_Environment::class);
        $function = $this->createMock(Twig_SimpleFilter::class);

        $view->expects($this->once())->method('getTwig')->will($this->returnValue($twig));
        $view->expects($this->once())->method('createTwigFilter')->with($this->equalTo($name), $this->equalTo($callable))->will($this->returnValue($function));            
        $view->expects($this->never())->method('createTwigFunction');            
        $twig->expects($this->once())->method('addFilter')->with($this->equalTo($function));

        $result = $view->setViewFunction($name, $callable, 'filter');

        $this->assertEquals($view, $result, "Method should return \$this");
    }

    /**
     * Provide data for testing 'setViewFunction' method when creating filter
     *
     * @return array
     */
    public function setViewFunctionFilterProvider()
    {
        return [
            ['test_name', function() {}]
        ];
    }

    /**
     * Test 'setViewVariable' method
     *
     * @dataProvider setViewVariableProvider
     */
    public function testSetViewVariable($name, $value)
    {
        $view = $this->getView(['getTwig']);
        $twig = $this->createMock(Twig_Environment::class);

        if (!$name) {
            $this->expectException(InvalidArgumentException::class);
        } else {
            $view->expects($this->once())->method('getTwig')->will($this->returnValue($twig));
            $twig->expects($this->once())->method('addGlobal')->with($this->equalTo($name), $this->equalTo($value));            
        }

        $result = $view->setViewVariable($name, $value);

        $this->assertEquals($view, $result, "Method should return \$this");
    }

    /**
     * Provide data for testing 'setViewVariable' method
     *
     * @return array
     */
    public function setViewVariableProvider()
    {
        return [
            ['test_name', 'test_value'],
            ['test_name', ''],
            ['', 'test_value'],
        ];  
    }

    /**
     * Test edding extension to twig
     */
    public function testSetViewExtension()
    {
        $view = $this->getView(['getTwig']);
        $twig = $this->createMock(Twig_Environment::class);
        $ext = $this->createMock(Twig_ExtensionInterface::class);

        $view->expects($this->once())->method('getTwig')->will($this->returnValue($twig));
        $twig->expects($this->once())->method('addExtension')->with($this->equalTo($ext));        

        $result = $view->setViewExtension($ext);

        $this->assertEquals($view, $result, "Method should return \$this");
    }

    /**
     * Get mock for testing trait
     *
     * @param array $methods  Methods to mock
     * @return Twig
     */
    public function getView($methods = [])
    {
        return $this->getMockForTrait(Twig::class, [], '', true, true, true, $methods);
    }

    /**
     * Get mocks representing twig objects
     *
     * @return array
     */
    public function getTwigObjects()
    {
        return [
            $this->createMock(Twig_Loader_Filesystem::class),
            $this->createMock(Twig_Environment::class),
            $this->createMock(Twig_Template::class)
        ];
    }

    /**
     * Get request and response instances
     *
     * @return array
     */
    public function getRequests()
    {
        return [
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(ResponseInterface::class)            
        ];
    }
}
