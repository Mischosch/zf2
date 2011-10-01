<?php

namespace ZendTest\Mvc\Router\Http;

use PHPUnit_Framework_TestCase as TestCase,
    Zend\Mvc\Router\Http\Route,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mvc\Router,
    Zend\Uri\UriFactory;

class RouteTest extends TestCase
{
    public function setUp()
    {
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithoutOptions()
    {
        $route = new Route();
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithStringOptions()
    {
        $route = new Route('');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithEmptyDefaults()
    {
        $route = new Route(array('route' => ''));
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithoutRouteOption()
    {
        $route = new Route(array('defaults' => array()));
    }
    

    public static function validMatches()
    {
        return array(
            array(
                array(
                    'namespace'  => 'project',
                    'controller' => 'index',
                    'action'     => 'index'
                ),
                '/project/:controller/:action/*',
                'http://example.com/project/list/add',
                array(
                    'namespace'  => 'project',
                    'controller' => 'list',
                    'action'     => 'add'
                ),
            ), 
            array(
                array(
                    'namespace'  => 'application',
                    'controller' => 'index',
                    'action'     => 'index',
                    'id'         => ''
                ),
                '/application/:controller/:action/:id/*',
                'http://example.com/application/blog/read/23/test/123',
                array(
                    'namespace'  => 'application',
                    'controller' => 'blog',
                    'action'     => 'read',
                    'id'         => '23',
                    'test'       => '123'
                ),
            ), 
            array(
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'index'
                ),
                '/newsletter/:controller/:action/*',
                'http://example.com/newsletter/user/password',
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'password'
                ),
            ), 
            array(
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'reset',
                    'hash'       => ''
                ),
                '/newsletter/r/:hash',
                'http://example.com/newsletter/r/abc123',
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'reset',
                    'hash'       => 'abc123'
                ),
            ), 
        );
    }

    /**
     * @dataProvider validMatches
     */
    public function testMatches($defaults, $route, $url, $assertion)
    {
        $router    = $this->setupRouter($route, $defaults);
        $request   = $this->setupRequest($url);
        $matches   = $router->match($request);
        $params    = $matches->getParams();
        asort($params);
        asort($assertion);
        $this->assertSame($assertion, $params);
    }
    
    
    public static function invalidMatches()
    {
        return array(
            array(
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'index',
                    'action'     => 'index'
                ),
                '/newsletter/:controller/:action/*',
                'http://example.com/test/user/password'
            ), 
            array(
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'reset',
                    'hash'       => ''
                ),
                '/newsletter/r/:hash',
                'http://example.com/newsletter/a/abc123'
            ), 
        );
    }
    
    /**
     * @dataProvider invalidMatches
     */
    public function testMatchErrors($defaults, $route, $url)
    {
        $router  = $this->setupRouter($route, $defaults);
        $request = $this->setupRequest($url);
        $matches = $router->match($request);
        $this->assertSame(null, $matches);
    }
    
    /**
     * @dataProvider validMatches
     */
    public function testAssemble($defaults, $route, $url, $params)
    {
        $router  = $this->setupRouter($route, $defaults);
        $request = $this->setupRequest($url);
        $router->match($request);
        
        $assembledUri = $router->assemble($params);
        $this->assertSame(true, is_int(strpos($url, $assembledUri)));
    }
    

    protected function setupRouter($route, $defaults)
    {
        return new Route(array(
            'route' => $route,
            'defaults' => $defaults
        ));
    }

    protected function setupRequest($url)
    {
        $request = new Request();
        $uri     = UriFactory::factory($url);
        $request->setUri($uri);
        return $request;
    }

}
