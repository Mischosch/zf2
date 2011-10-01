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
    Zend\Mvc\Router\Route as RouteInterface,
    Zend\Mvc\Router\RouteMatch;

/**
 * Route.
 *
 * @package    Zend_Router
 * @subpackage Route
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Route implements RouteInterface
{
    protected $urlVariable = ':';
    protected $urlDelimiter = '/';
    protected $regexDelimiter = '#';
    protected $defaultRegex = null;

    /**
     * Route to match.
     * 
     * @var string
     */
    protected $route;

    /**
     * Holds user submitted default values for route's variables. Name and value pairs.
     *
     * @var array
     */
    protected $defaults = array();

    /**
     * Holds names of all route's pattern variable names. Array index holds a position in URL.
     * @var array
     */
    protected $variables = array();

    /**
     * Holds Route patterns for all URL parts. In case of a variable it stores it's regex
     * requirement or null. In case of a static part, it holds only it's direct value.
     * In case of a wildcard, it stores an asterisk (*)
     *
     * @var array
     */
    protected $parts = array();

    /**
     * Holds user submitted regular expression patterns for route's variables' values.
     * Name and value pairs.
     *
     * @var array
     */
    protected $requirements = array();

    /**
     * Associative array filled on match() that holds matched path values
     * for given variable names.
     *
     * @var array
     */
    protected $values = array();

    /**
     * Associative array filled on match() that holds wildcard variable
     * names and values.
     *
     * @var array
     */
    protected $wildcardData = array();

    /**
     * Helper var that holds a count of route pattern's static parts
     * for validation
     *
     * @var int
     */
    protected $staticCount = 0;
    
    
    

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

        if (!isset($options['route']) || !is_string($options['route'])) {
            throw new Exception\InvalidArgumentException('Route not defined nor not a string');
        }
        
        if (!isset($options['defaults']) || !is_array($options['defaults'])) {
            throw new Exception\InvalidArgumentException('Defaults not defined nor not an array');
        }
        
        $this->route         = trim($options['route'], $this->urlDelimiter);
        $this->defaults      = $options['defaults'];
        $this->requirements  = array();
        if (!empty($options['reqs'])) {
            $this->requirements = $options['reqs'];
        }

        if ($this->route !== '') {
            foreach (explode($this->urlDelimiter, $this->route) as $pos => $part) {
                if (substr($part, 0, 1) == $this->urlVariable && substr($part, 1, 1) != $this->urlVariable) {
                    $name                  = substr($part, 1);
                    $this->parts[$pos]     = (isset($reqs[$name]) ? $reqs[$name] : $this->defaultRegex);
                    $this->variables[$pos] = $name;
                } else {
                    if (substr($part, 0, 1) == $this->urlVariable) {
                        $part = substr($part, 1);
                    }

                    $this->parts[$pos] = $part;

                    if ($part !== '*') {
                        $this->staticCount++;
                    }
                }
            }
        }
        
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
        $pathStaticCount = 0;
        $values          = array();
        $matchedPath     = '';
        $uri             = $request->uri();
        $path            = $uri->getPath();

        if (!$pathOffset) {
            $path = trim($path, $this->urlDelimiter);
        }

        if ($path !== '') {
            $path = explode($this->urlDelimiter, $path);

            foreach ($path as $pos => $pathPart) {
            
                // Path is longer than a route, it's not a match
                if (!array_key_exists($pos, $this->parts)) {
                    if ($pathOffset) {
                        break;
                    } else {
                        return null;
                    }
                }

                $matchedPath .= $pathPart . $this->urlDelimiter;

                // If it's a wildcard, get the rest of URL as wildcard data and stop matching
                if ($this->parts[$pos] == '*') {
                    $count = count($path);
                    for($i = $pos; $i < $count; $i+=2) {
                        $var = urldecode($path[$i]);
                        if (!isset($this->wildcardData[$var]) && !isset($this->defaults[$var]) && !isset($values[$var])) {
                            $this->wildcardData[$var] = (isset($path[$i+1])) ? urldecode($path[$i+1]) : null;
                        }
                    }

                    $matchedPath = implode($this->urlDelimiter, $path);
                    break;
                }

                $name     = isset($this->variables[$pos]) ? $this->variables[$pos] : null;
                $pathPart = urldecode($pathPart);
                $part = $this->parts[$pos];

                // If it's a static part, match directly
                if ($name === null && $part != $pathPart) {
                    return null;
                }

                // If it's a variable with requirement, match a regex. If not - everything matches
                if ($part !== null && !preg_match($this->regexDelimiter . '^' . $part . '$' . $this->regexDelimiter . 'iu', $pathPart)) {
                    return null;
                }

                // If it's a variable store it's value for later
                if ($name !== null) {
                    $values[$name] = $pathPart;
                } else {
                    $pathStaticCount++;
                }
            }
        }

        // Check if all static mappings have been matched
        if ($this->staticCount != $pathStaticCount) {
            return null;
        }

        $this->matches = $values + $this->wildcardData + $this->defaults;
        
        // Check if all map variables have been initialized
        foreach ($this->variables as $var) {
            if (!array_key_exists($var, $this->matches)) {
                return null;
            }
        }

        
        //$this->setMatchedPath(rtrim($matchedPath, $this->urlDelimiter));

        return new RouteMatch($this->matches, $this);
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
        $url  = array();
        $flag = false;

        foreach ($this->parts as $key => $part) {
            $name = isset($this->variables[$key]) ? $this->variables[$key] : null;

            $useDefault = false;
            if (isset($name) && array_key_exists($name, $params) && $params[$name] === null) {
                $useDefault = true;
            }

            if (isset($name)) {
                if (isset($params[$name]) && !$useDefault) {
                    $value = $params[$name];
                    unset($params[$name]);
                } elseif (!$reset && !$useDefault && isset($this->values[$name])) {
                    $value = $this->_values[$name];
                } elseif (!$reset && !$useDefault && isset($this->wildcardData[$name])) {
                    $value = $this->wildcardData[$name];
                } elseif (isset($this->defaults[$name])) {
                    $value = $this->defaults[$name];
                } else {
                    throw new Exception\InvalidArgumentException($name . ' is not specified');
                }

                $url[$key] = $value;
            } elseif ($part != '*') {
                if (substr($part, 0, 2) === '@@') {
                    $part = substr($part, 1);
                }

                $url[$key] = $part;
            } else {
                if (!$options['reset']) $params += $this->wildcardData;
                $defaults = $this->getDefaults();
                foreach ($params as $var => $value) {
                    if ($value !== null && (!isset($defaults[$var]) || $value != $defaults[$var])) {
                        $url[$key++] = $var;
                        $url[$key++] = $value;
                        $flag = true;
                    }
                }
            }
        }

        $assembledUrl = '';

        foreach (array_reverse($url, true) as $key => $value) {
            $defaultValue = null;

            if (isset($this->variables[$key])) {
                $defaultValue = $this->getDefault($this->variables[$key]);
            }

            if ($flag || $value !== $defaultValue || $options['pathOffset']) {
                if ($options['encode']) $value = urlencode($value);
                $assembledUrl = $this->urlDelimiter . $value . $assembledUrl;
                $flag = true;
            }
        }

        return trim($assembledUrl, $this->urlDelimiter);
    }
    
    
    /**
     * Return a single parameter of route's defaults
     *
     * @param string $name Array key of the parameter
     * @return string Previously set default
     */
    public function getDefault($name) {
        if (isset($this->defaults[$name])) {
            return $this->defaults[$name];
        }
        return null;
    }

    /**
     * Return an array of defaults
     *
     * @return array Route defaults
     */
    public function getDefaults() {
        return $this->defaults;
    }
    
}
