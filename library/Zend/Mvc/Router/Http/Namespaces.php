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
 * @package    Zend_Router
 * @subpackage Route
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Mvc\Router\Http;

use Traversable,
    Zend\Config\Config,
    Zend\Http\Request,
    Zend\Mvc\Router\Exception,
    Zend\Mvc\Router\Route,
    Zend\Mvc\Router\RouteMatch;

/**
 * Namespaces route.
 *
 * @package    Zend_Router
 * @subpackage Route
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Namespaces implements Route
{
    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';
    
    /**
     * Default values for the route (ie. namespace, controller, action, params)
     *
     * @var array
     */
    protected $defaults;
    
    /**
     * Given Namespace
     *
     * @var array
     */
    protected $namespace;
    
    /**
     * Matches
     * 
     * @var array
     */
    protected $matches = array();

    
    /**
     * Array keys to use for namespace, controller, and action. 
     * Should be taken out of request.
     *
     * @var string
     */
    protected $namespaceKey  = 'namespace';
    protected $controllerKey = 'controller';
    protected $actionKey     = 'action';
    
    /**
     * __construct(): defined by Route interface.
     *
     * @see    Route::__construct()
     * @param  mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Config) {
            $options = $options->toArray();
        } elseif ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException('Options must either be an array or a Traversable object');
        }
        
        if (!isset($options['namespace']) || !is_string($options['namespace'])) {
            throw new Exception\InvalidArgumentException('Namespace not defined nor not a string');
        }
        
        if (!isset($options['defaults']) || !is_array($options['defaults'])) {
            throw new Exception\InvalidArgumentException('Defaults not defined nor not an array');
        }
        
        $this->defaults = isset($options['defaults']) ? $options['defaults'] : array();
        $this->namespace = isset($options['namespace']) ? $options['namespace'] : 'application';
    }

    /**
     * match(): defined by Route interface.
     *
     * @see    Route::match()
     * @param  Request $request
     * @return RouteMatch
     */
    public function match(Request $request, $pathOffset = null)
    {
        $uri  = $request->uri();
        $path = $uri->getPath();
        
        if ($path != '') {
            $path = trim($path, self::URI_DELIMITER);
            $path = explode(self::URI_DELIMITER, $path);
            
            if ($path[0] != $this->namespace) {
                return null;
            }
            
            $values = array();
            $values[$this->namespaceKey] = array_shift($path);
            $values[$this->controllerKey] = array_shift($path);

            if (count($path) && !empty($path[0])) {
                $values[$this->actionKey] = array_shift($path);
            }
            
            $params = array();
            if ($numSegs = count($path)) {
                for ($i = 0; $i < $numSegs; $i = $i + 2) {
                    $key = urldecode($path[$i]);
                    $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                    $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
                }
            }
        }

        if (empty($values)) {
            return null;
        }

        $match = $values + $params;
        
        foreach ($match as $key => $value) {
            if (is_numeric($key) || is_int($key)) {
                unset($match[$key]);
            }
        }

        $matches       = array_merge($this->defaults, $match);
        $this->matches = $matches;
        return new RouteMatch($matches, $this);
    }

    /**
     * assemble(): Defined by Route interface.
     *
     * @see    Route::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = null, array $options = null)
    {
        $params = (array) $params;
        $values = array_merge($this->matches, $params);

        $url = '';

        $namespace = $values[$this->namespaceKey];
        unset($values[$this->namespaceKey]);

        $controller = $values[$this->controllerKey];
        unset($values[$this->controllerKey]);

        $action = $values[$this->actionKey];
        unset($values[$this->actionKey]);

        foreach ($values as $key => $value) {
            $key = (isset($options['encode'])) ? urlencode($key) : $key;
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $arrayValue = (isset($options['encode'])) ? urlencode($arrayValue) : $arrayValue;
                    if (empty($arrayValue)) continue;
                    $url .= '/' . $key;
                    $url .= '/' . $arrayValue;
                }
            } else {
                if (isset($options['encode'])) $value = urlencode($value);
                if (empty($value)) continue;
                $url .= '/' . $key;
                $url .= '/' . $value;
            }
        }
        
        if (!empty($url) || $action !== $this->defaults[$this->actionKey]) {
            if (isset($options['encode'])) $action = urlencode($action);
            $url = '/' . $action . $url;
        }

        if (isset($options['encode'])) $controller = urlencode($controller);
        $url = '/' . $controller . $url;
        
        if (isset($options['encode'])) $namespace = urlencode($namespace);
        $url = '/' . $namespace . $url;
        
        return $url;
    }
    
}
