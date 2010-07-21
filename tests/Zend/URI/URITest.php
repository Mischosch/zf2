<?php 

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_URI
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id $
 */

/**
 * @namespace
 */
namespace ZendTest\URI;
use Zend\URI\URI;

/**
 * @category   Zend
 * @package    Zend_URI
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_URI
 */
class URITest extends \PHPUnit_Framework_TestCase
{   
    /**
     * General composing / parsing tests
     */ 
    
    /**
     * Test that parsing and composing a valid URI returns the same URI
     *
     * @param        string $uriString
     * @dataProvider validUriStringProvider
     */
    public function testParseComposeURI($uriString)
    {
        $uri = new URI($uriString);
        $this->assertEquals($uriString, $uri->generate());
    }

    /**
     * Test composing a new URI by setting the different URI parts programatically.
     * 
     * Also tests casting a URI object to string.
     * 
     * @param string $exp
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testComposeNewURIandCastToString($exp, $parts)
    {
        $uri = new URI;
        foreach($parts as $k => $v) {
            $setMethod = 'set' . ucfirst($k);
            $uri->$setMethod($v);
        }
        
        $this->assertEquals($exp, (string) $uri);
    }
        
    /**
     * Accessor Tests
     */
    
    /**
     * Test that we can get the scheme out of a parsed URI
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetScheme($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['scheme'])) {
            $this->assertEquals($parts['scheme'], $uri->getScheme());
        } else {
            $this->assertNull($uri->getScheme());
        }
    }
    
    /**
     * Test that we get the correct userInfo
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetUserInfo($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['userInfo'])) {
            $this->assertEquals($parts['userInfo'], $uri->getUserInfo());
        } else {
            $this->assertNull($uri->getUserInfo());
        }
    }
    
    /**
     * Test that we can get the host out of a parsed URI
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetHost($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['host'])) {
            $this->assertEquals($parts['host'], $uri->getHost());
        } else {
            $this->assertNull($uri->getHost());
        }
    }

    /**
     * Test that we can get the port out of a parsed URI
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetPort($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['port'])) {
            $this->assertEquals($parts['port'], $uri->getPort());
        } else {
            $this->assertNull($uri->getPort());
        }
    }

    /**
     * Test that we can get the path out of a parsed URI
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetPath($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['path'])) {
            $this->assertEquals($parts['path'], $uri->getPath());
        } else {
            $this->assertNull($uri->getPath());
        }
    }
    
    /**
     * Test that we can get the query out of a parsed URI
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetQuery($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['query'])) {
            $this->assertEquals($parts['query'], $uri->getQuery());
        } else {
            $this->assertNull($uri->getQuery());
        }
    }

    /**
     * @group ZF-1480
     */
    public function testGetQueryAsArrayReturnsCorrectArray()
    {
        $url = new URI('http://example.com/foo/?test=a&var[]=1&var[]=2&some[thing]=3');
        $this->assertEquals('test=a&var[]=1&var[]=2&some[thing]=3', $url->getQuery());
        
        $exp = array(
            'test' => 'a',
            'var'  => array(1, 2),
            'some' => array('thing' => 3)
        );
        
        $this->assertEquals($exp, $url->getQueryAsArray());
    }
    
    /**
     * Test that we can get the fragment out of a parsed URI
     * 
     * @param string $uriString
     * @param array  $parts
     * @dataProvider uriWithPartsProvider
     */
    public function testGetFragment($uriString, $parts)
    {
        $uri = new URI($uriString);
        if (isset($parts['fragment'])) {
            $this->assertEquals($parts['fragment'], $uri->getFragment());
        } else {
            $this->assertNull($uri->getFragment());
        }
    }

    /**
     * Mutator Tests
     */
    
    /**
     * Test we can set the scheme to NULL
     * 
     */
    public function testSetSchemeNull()
    {
        $uri = new URI('http://example.com');
        $this->assertEquals('http', $uri->getScheme());
        
        $uri->setScheme(null);
        $this->assertNull($uri->getScheme());
    }
    
    /**
     * Test we can set different valid schemes
     *  
     * @param string $scheme
     * @dataProvider validSchemeProvider
     */
    public function testSetSchemeValid($scheme)
    {
        $uri = new URI;
        $uri->setScheme($scheme);
        $this->assertEquals($scheme, $uri->getScheme());
    } 
    
    /**
     * Test that setting an invalid scheme causes an exception
     * 
     * @param string $scheme
     * @dataProvider invalidSchemeProvider
     * @expectedException \Zend\URI\InvalidSchemeException
     */
    public function testSetInvalidScheme($scheme)
    {
        $uri = new URI;
        $uri->setScheme($scheme);
    }
    
    
    /**
     * Validation and encoding tests
     */
    
    /**
     * Test that valid URIs pass validation
     * 
     * @param string $uriString
     * @dataProvider validUriStringProvider
     */
    public function testValidUriIsValid($uriString)
    {
        $uri = new URI($uriString);
        $this->assertTrue($uri->isValid());
    }

    /**
     * Test that invalid URIs pass validation
     *
     * @param \Zend\URI\URI $uri 
     * @dataProvider invalidUriObjectProvider
     */
    public function testInvalidUriIsInvalid(URI $uri)
    {
        $this->markTestSkipped();
        $this->assertFalse($uri->isValid());
    }
    
    /**
     * Check that valid schemes are valid according to validateScheme()
     * 
     * @param string $scheme
     * @dataProvider validSchemeProvider
     */
    public function testValidateSchemeValid($scheme)
    {
        $this->assertTrue(URI::validateScheme($scheme));
    }
    
    /**
     * Check that invalid schemes are invalid according to validateScheme()
     * 
     * @param string $scheme
     * @dataProvider invalidSchemeProvider
     */
    public function testValidSchemeInvalid($scheme)
    {
        $this->assertFalse(URI::validateScheme($scheme));
    }
    
    /**
     * Check that valid hosts are valid according to validateHost()
     * 
     * @param string $host
     * @dataProvider validHostProvider
     */
    public function testValidateHostValid($host)
    {
        $this->assertTrue(URI::validateHost($host));
    }
    
    /**
     * Check that invalid hosts are invalid according to validateHost()
     * 
     * @param string $host
     * @dataProvider invalidHostProvider
     */
    public function testValiteHostInvalid($host)
    {
        $this->assertFalse(URI::validateHost($host));
    }
    
    /**
     * Test that valid query or fragment parts are validated properly
     * 
     * @param $input
     * @dataProvider validQueryFragmentProvider
     */
    public function testValidQueryFragment($input)
    {
        $this->assertTrue(URI::validateQueryFragment($input));
    }
    
    /**
     * Test that invalid query or fragment parts are validated properly
     * 
     * @param $input
     * @dataProvider invalidQueryFragmentProvider
     */
    public function testInvalidQueryFragment($input, $exp)
    {
        $this->assertFalse(URI::validateQueryFragment($input));
    }
    
    /**
     * Test that valid query or fragment parts properly encoded
     *
     * @param $input
     * @param $exp
     * @dataProvider invalidQueryFragmentProvider
     */
    public function testEncodeInvalidQueryFragment($input, $exp)
    {
        $actual = URI::encodeQueryFragment($input);
        $this->assertEquals($exp, $actual);
    }
    
    /**
     * Test that valid query or fragment parts are not modified when paseed 
     * through encodeQueryFragment()
     *
     * @param $input
     * @param $exp
     * @dataProvider validQueryFragmentProvider
     */
    public function testEncodeValidQueryFragment($input)
    {
        $actual = URI::encodeQueryFragment($input);
        $this->assertEquals($input, $actual);
    }
    
    /**
     * Test that valid userInfo input is validated by validateUserInfo
     * 
     * @param string $userInfo
     * @dataProvider validUserInfoProvider
     */
    public function testValidateUserInfoValid($userInfo)
    {
        $this->assertTrue(URI::validateUserInfo($userInfo));
    }
    
    /**
     * Test that invalid userInfo input is not accepted by validateUserInfo
     * 
     * @param string $userInfo
     * @param string $exp 
     * @dataProvider invalidUserInfoProvider
     */
    public function testValidateUserInfoInvalid($userInfo, $exp)
    {
        $this->assertFalse(URI::validateUserInfo($userInfo));
    }
    
    /**
     * Test that valid userInfo is returned unchanged by encodeUserInfo
     * 
     * @param $userInfo
     * @dataProvider validUserInfoProvider
     */
    public function testEncodeUserInfoValid($userInfo)
    {
        $this->assertEquals($userInfo, URI::encodeUserInfo($userInfo));
    }
    
    /**
     * Test that invalid userInfo input properly encoded by encodeUserInfo
     * 
     * @param string $userInfo
     * @param string $exp 
     * @dataProvider invalidUserInfoProvider
     */
    public function testEncodeUserInfoInvalid($userInfo, $exp)
    {
        $this->assertEquals($exp, URI::encodeUserInfo($userInfo));
    }
    
    /**
     * Test that validatePort works for valid ports
     * 
     * @param mixed $port
     * @dataProvider validPortProvider
     */
    public function testValidatePortValid($port)
    {
        $this->assertTrue(URI::validatePort($port));
    }
    
    /**
     * Test that validatePort works for invalid ports
     * 
     * @param mixed $port
     * @dataProvider invalidPortProvider
     */
    public function testValidatePortInvalid($port)
    {
        $this->assertFalse(URI::validatePort($port));
    }
    
    /**
     * @group ZF-1480
     */
    /*
    public function testAddReplaceQueryParametersModifiesQueryAndReturnsOldQuery()
    {
        $url = new URI('http://example.com/foo/?a=1&b=2&c=3');
        $url->addReplaceQueryParameters(array('b' => 4, 'd' => -1));
        $this->assertEquals(array(
            'a' => 1,
            'b' => 4,
            'c' => 3,
            'd' => -1
        ), $url->getQueryAsArray());
        $this->assertEquals('a=1&b=4&c=3&d=-1', $url->getQuery());
    }
    */

    /**
     * @group ZF-1480
     */
    /*
    public function testRemoveQueryParametersModifiesQueryAndReturnsOldQuery()
    {
        $url = new URI('http://example.com/foo/?a=1&b=2&c=3&d=4');
        $url->removeQueryParameters(array('b', 'd', 'e'));
        $this->assertEquals(array('a' => 1, 'c' => 3), $url->getQueryAsArray());
        $this->assertEquals('a=1&c=3', $url->getQuery());
    }
    */
    
    /**
     * Other tests
     */
    
    /**
     * Test the fluent interface
     * 
     * @param string $method
     * @param string $params
     * @dataProvider fluentInterfaceMethodProvider
     */
    public function testFluentInterface($method, $params)
    {
        $uri = new URI;
        $ret = call_user_func_array(array($uri, $method), $params);
        $this->assertSame($uri, $ret);
    }
    
    /**
     * Data Providers
     */
    
    static public function validUserInfoProvider()
    {
        return array(
            array('user:'),
            array(':password'),
            array('user:password'),
            array(':'),
            array('my-user'),
            array('one:two:three:four'),
            array('my-user-has-%3A-colon:pass'),
            array('a_.!~*\'(-)n0123Di%25%26:pass;:&=+$,word')
        );
    }
    
    static public function invalidUserInfoProvider()
    {
        return array(
            array('an`di:password',    'an%60di:password'),
            array('user name',         'user%20name'),
            array('shahar.e@zend.com', 'shahar.e%40zend.com')
        );
    }
    
    /**
     * Data provider for valid URIs, not necessarily complete 
     * 
     * @return array
     */
    static public function validUriStringProvider()
    {
        return array(
            array('a:b'),
            array('http://www.zend.com'),
            array('https://example.com:10082/foo/bar?query'),
            array('../relative/path'),
            array('?queryOnly'),
            array('#fragmentOnly'),
            array('mailto:bob@example.com'),
            array('bob@example.com'),
            array('http://a_.!~*\'(-)n0123Di%25%26:pass;:&=+$,word@www.zend.com')
        );
    }
    
    /**
     * Valid schemes 
     * 
     * @return array
     */
    static public function validSchemeProvider()
    {
        return array(
            // Valid schemes
            array('http'),
            array('HTTP'),
            array('File'),
            array('h'),
            array('h2'),
            array('a+b'),
            array('k-'),
         );
    }
    
    /**
     * Invalid schemes
     * 
     * @return array
     */
    static public function invalidSchemeProvider() 
    {
        return array(
            array('ht tp'),
            array('htp_p'),
            array('-tp'),
            array('22c'),
            array('h%acp'),
        );
    }
    
    /**
     * Valid query or fragment parts
     * 
     * Each valid query or fragment part should require no encoding and if 
     * passed throuh an encoding method shoudl return unchanged. 
     * 
     * @return array
     */
    static public function validQueryFragmentProvider()
    {
        return array(
            array('a=1&b=2&c=3&d=4'),
            array('with?questionmark/andslash'),
            array('id=123&url=http://example.com/?bar=foo+baz'),
            array('with%20%0Aline%20break'), 
        );
    }
    
    /**
     * Invalid query or fragment parts. 
     * 
     * Additionally, this method supplies a valid, URL-encoded representation
     * of each invalid part, which can be used to test encoding. 
     * 
     * @return array
     */
    static public function invalidQueryFragmentProvider()
    {
        return array(
            array('with#pound', 'with%23pound'),
            array('with space', 'with%20space'),
            array('test=a&var[]=1&var[]=2&some[thing]=3', 'test=a&var%5B%5D=1&var%5B%5D=2&some%5Bthing%5D=3'),
            array("with \nline break", "with%20%0Aline%20break"),
            array("with%percent", "with%25percent"),
        );
    }
    
    /**
     * Data provider for invalid URIs 
     * 
     * @return array
     */
    static public function invalidUriStringProvider()
    {
        return array(
            array(':path'),
            array(null)
        );
    }
    
    /**
     * Data provider for invalid URI objects
     * 
     * @return array
     */
    static public function invalidUriObjectProvider()
    {
        // Empty URI is not valid
        $obj1 = new URI;
        
        // Path cannot begin with '//' if there is no authority part
        $obj2 = new URI;
        $obj2->setPath('//path');

        // A relative URI cannot have a path beginning with ':'
        $obj3 = new URI;
        $obj3->setPath(':path');
        
        return array(
            array($obj1),
            array($obj2),
            array($obj3)
        );
    }
    
    
    /**
     * Data provider for valid URIs with their different parts
     * 
     * @return array
     */
    static public function uriWithPartsProvider()
    {
        return array(
            array('ht-tp://server/path?query', array(
                'scheme'   => 'ht-tp', 
                'host'     => 'server',
                'path'     => '/path',
                'query'    => 'query',
            )),
            array('file:///foo/bar', array(
                'scheme'   => 'file',
                'host'     => '', 
                'path'     => '/foo/bar',
            )),
            array('http://dude:lebowski@example.com/#fr/ag?me.nt', array(
                'scheme'   => 'http',
                'userInfo' => 'dude:lebowski',
                'host'     => 'example.com',
                'path'     => '/',
                'fragment' => 'fr/ag?me.nt'
            )),
            array('/relative/path', array(
                'path' => '/relative/path'
            )),
            array('ftp://example.com:5555', array(
                'scheme' => 'ftp',
                'host'   => 'example.com',
                'port'   => 5555,
                'path'   => ''
            )),
            array('http://example.com/foo//bar/baz//fob/', array(
                'scheme' => 'http',
                'host'   => 'example.com',
                'path'   => '/foo//bar/baz//fob/'
            ))
        );
    }
    
    /**
     * Provider for valid ports
     * 
     * @return array
     */
    static public function validPortProvider()
    {
        return array(
            array(null),
            array(1),
            array(0xffff),
            array(80),
            array('443')
        );
    }
    
    /**
     * Provider for invalid ports
     *
     * @return array
     */
    static public function invalidPortProvider()
    {
        return array(
            array(0),
            array(-1),
            array(0x10000),
            array('foo'),
            array('0xf'),
            array('-'),
            array(':'),
            array('/')
        );
    }
    
    static public function validHostProvider()
    {
        return array(
            // IPv4 addresses
            array('10.1.2.3'),
            array('127.0.0.1'),
            array('0.0.0.0'),
            array('255.255.255.255'),
            
            // IPv6 addresses
            // Examples from http://en.wikipedia.org/wiki/IPv6_address
            array('[2001:0db8:85a3:0000:0000:8a2e:0370:7334]'),
            array('[2001:db8:85a3:0:0:8a2e:370:7334]'),
            array('[2001:db8:85a3::8a2e:370:7334]'),
            array('[0:0:0:0:0:0:0:1]'),
            array('[::1]'),
            array('[2001:0db8:85a3:08d3:1319:8a2e:0370:7348]'),
            
            // Internet and local DNS names
            array('www.example.com'),
            array('zend.com'),
            array('php-israel.org'),
            array('arr.gr'),
            array('localhost'),
            array('loca.host'),
            array('zend-framework.test'),
            array('a.b.c.d'),
            array('a1.b2.c3.d4'),
            array('some-domain-with-dashes'),
        
            // Registered name (other than DNS names)
            array('some~unre_served.ch4r5'),
            array('pct.%D7%A9%D7%97%D7%A8%20%D7%94%D7%92%D7%93%D7%95%D7%9C.co.il'),
            array('sub-delims-!$&\'()*+,;=.are.ok'),
            array('%2F%3A')
        );
    }
    
    static public function invalidHostProvider()
    {
        
    }
    
    /**
     * Return all methods that are expected to return the same object they
     * are called on, to test the fluent interface
     * 
     * @return array
     */
    static public function fluentInterfaceMethodProvider()
    {
        return array(
            array('setScheme',    array('file')),
            array('setUserInfo',  array('userInfo')),
            array('setHost',      array('example.com')),
            array('setPort',      array(80)),
            array('setPath',      array('/baz/baz')),
            array('setQuery',     array('foo=bar')),
            array('setFragment',  array('part2')),
            array('makeRelative', array('http://foo.bar/')),
            array('resolve',      array('http://foo.bar/')),
            array('normalize',    array())
        );
    }
}