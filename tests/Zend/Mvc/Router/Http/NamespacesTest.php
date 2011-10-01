<?php

namespace ZendTest\Mvc\Router\Http;

use PHPUnit_Framework_TestCase as TestCase,
    Zend\Mvc\Router\Http\Namespaces,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mvc\Router,
    Zend\Uri\UriFactory;

class NamespacesTest extends TestCase
{
    public function setUp()
    {
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithoutOptions()
    {
        $route = new Namespaces();
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithEmptyOptionsArray()
    {
        $route = new Namespaces(array());
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRouteWithStringDefaultsOption()
    {
        $route = new Namespaces(array('defaults' => ''));
    }
    

    public static function validMatches()
    {
        return array(
            array(
                array(
                    'namespace'  => 'application',
                    'controller' => 'index',
                    'action'     => 'index'
                ),
                'http://example.com/application/index'
            ), 
            array(
                array(
                    'namespace'  => 'project',
                    'controller' => 'list',
                    'action'     => 'add'
                ),
                'http://example.com/project/list/add'
            ), 
            array(
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'password'
                ),
                'http://example.com/newsletter/user/password'
            ), 
            array(
                array(
                    'namespace'  => 'newsletter',
                    'controller' => 'user',
                    'action'     => 'reset',
                    'hash'       => '123abc'
                ),
                'http://example.com/newsletter/user/reset/hash/123abc'
            ), 
        );
    }

    /**
     * @dataProvider validMatches
     */
    public function testMatches($assertion, $url)
    {
        $router  = $this->setupRouter($assertion['namespace']);
        $request = $this->setupRequest($url);
        $matches = $router->match($request);
        $this->assertSame($assertion, $matches->getParams());
    }
    
    
    public static function invalidMatches()
    {
        return array(
            array(
                'application',
                'http://example.com/test/foo/bar',
            ), 
            array(
                'project',
                'http://example.com/project_add/index/index'
            ), 
            array(
                'newsletter', 
                'http://example.com/newsletter-user-password'
            ), 
        );
    }
    
    /**
     * @dataProvider invalidMatches
     */
    public function testMatchErrors($namespace, $url)
    {
        $router  = $this->setupRouter($namespace);
        $request = $this->setupRequest($url);
        $matches = $router->match($request);
        $this->assertSame(null, $matches);
    }
    
    /**
     * @dataProvider validMatches
     */
    public function testAssemble($params, $url)
    {
        $router  = $this->setupRouter();
        $request = $this->setupRequest($url);
        $router->match($request);
        
        $assembledUri = $router->assemble($params);
        $this->assertSame(true, is_int(strpos($url, $assembledUri)));
    }
    

    protected function setupRouter($namespace = 'application')
    {
        return new Namespaces(array(
            'namespace' => $namespace, 
            'defaults' => array(
                'namespace'  => $namespace,
                'controller' => 'index', 
                'action'     => 'index'
            )
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
