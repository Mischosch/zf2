<?php

namespace Zend\Module;

use Traversable,
    Zend\Config\Config,
    Zend\EventManager\EventCollection,
    Zend\EventManager\EventManager;

class Manager
{
    /**
     * @var array An array of Module classes of loaded modules
     */
    protected $loadedModules = array();

    /**
     * @var EventCollection
     */
    protected $events;

    /**
     * @var ManagerOptions
     */
    protected $options;

    /**
     * @var Zend\Config\Config
     */
    protected $mergedConfig;

    /**
     * @var bool
     */
    protected $skipConfig = false;

    /**
     * __construct 
     * 
     * @param array|Traversable $modules 
     * @param ManagerOptions $options 
     * @return void
     */
    public function __construct($modules, ManagerOptions $options = null)
    {
        if ($options === null) {
            $this->setOptions(new ManagerOptions);
        } else {
            $this->setOptions($options);
        }
        if ($this->hasCachedConfig()) {
            $this->skipConfig = true;
            $this->setMergedConfig($this->getCachedConfig());
        }
        $this->loadModules($modules);
        $this->updateCache();
        $this->events()->trigger('init.post', $this);
    }

    /**
     * loadModules 
     * 
     * @param array|Traversable $modules 
     * @return Manager
     */
    public function loadModules($modules)
    {
        if (is_array($modules) || $modules instanceof Traversable) {
            foreach ($modules as $moduleName) {
                $this->loadModule($moduleName);
            }
        } else {
            throw new \InvalidArgumentException(
                'Parameter to \\ZendModule\\Manager\'s '
                . 'loadModules method must be an array or '
                . 'implement the \\Traversable interface'
            );
        }
        return $this;
    }

    /**
     * loadModule 
     * 
     * @param string $moduleName 
     * @return mixed Module's Module class
     */
    public function loadModule($moduleName)
    {
        if (!isset($this->loadedModules[$moduleName])) {
            $class = $moduleName . '\Module';
            $module = new $class;
            $this->runModuleInit($module);
            $this->mergeModuleConfig($module);
            $this->loadedModules[$moduleName] = $module;
        }
        return $this->loadedModules[$moduleName];
    }

    /**
     * Loop through loaded modules and verify that all dependencies are met 
     *
     * @TODO: 
     *  - This could probably be much more efficient (do not check satisfied 
     *  deps again, etc)
     *  - Do more isset() checking on the dep arrays
     * 
     * @return array An array of unsatisfied, optional dependencies
     */
    public function resolveDependencies()
    {
        foreach ($this->getLoadedModules() as $moduleName => $module) {
            if (!is_callable(array($module, 'getDependencies'))) {
                continue;
            }
            $unsatisfiedDeps = array();
            foreach ($module->getDependencies() as $dep => $depInfo) {
                preg_match('/(<|lt|<=|le|>|gt|>=|ge|==|=|eq|!=|<>|ne)?(\d.*)/',$depInfo['version'], $matches, PREG_OFFSET_CAPTURE);
                if ($dep === 'php') {
                    if (!version_compare(PHP_VERSION, $matches[2][0], $matches[1][0] ?: '>=')) {
                        if ($depInfo['required'] == true) {
                            throw new \RuntimeException("Required dependency unsatisfied: {$dep} {$depInfo['version']}");
                        } else {
                            $unsatifiedDeps[$moduleName][$dep] = $depInfo;
                        }
                    }
                } elseif (substr($dep, 0, 4) === 'ext/') {
                    $extName = substr($dep, 4);
                    if (!version_compare(phpversion($extName), $matches[2][0], $matches[1][0] ?: '>=')) {
                        if ($depInfo['required'] == true) {
                            throw new \RuntimeException("Required dependency unsatisfied: {$dep} {$depInfo['version']}");
                        } else {
                            $unsatifiedDeps[$moduleName][$dep] = $depInfo;
                        }
                    }
                } else {
                    $satisfied = false;
                    foreach ($this->getLoadedModules() as $depModuleName => $depModule) {
                        if (!is_callable(array($depModule, 'getProvides'))) {
                            continue;
                        }
                        $provides = $depModule->getProvides();
                        if ($provides['name'] !== $dep) {
                            continue;
                        }
                        if (version_compare($provides['version'], $matches[2][0], $matches[1][0] ?: '>=')) {
                            $satisfied = true;
                            break;
                        }
                    }
                    if (!$satisfied) {
                        if ($depInfo['required'] == true) {
                            throw new \RuntimeException("Required dependency unsatisfied: {$dep} {$depInfo['version']}");
                        } else {
                            $unsatifiedDeps[$moduleName][$dep] = $depInfo;
                        }
                    }
                }
            }
        }
        return $unsatisfiedDeps;
    }

    /**
     * Set the event manager instance used by this context
     * 
     * @param  EventCollection $events 
     * @return Manager
     */
    public function setEventManager(EventCollection $events)
    {
        $this->events = $events;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     * 
     * @return EventCollection
     */
    public function events()
    {
        if (!$this->events instanceof EventCollection) {
            $this->setEventManager(new EventManager(array(__CLASS__, get_class($this))));
        }
        return $this->events;
    }

    /**
     * Get options.
     *
     * @return ManagerOptions
     */
    public function getOptions()
    {
        return $this->options;
    }
 
    /**
     * Set options 
     * 
     * @param ManagerOptions $options 
     * @return Manager
     */
    public function setOptions(ManagerOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get loadedModules.
     *
     * @return array
     */
    public function getLoadedModules()
    {
        return $this->loadedModules;
    }

    /**
     * getMergedConfig
     * Build a merged config object for all loaded modules
     * 
     * @return Zend\Config\Config
     */
    public function getMergedConfig($readOnly = true)
    {
        if (null === $this->mergedConfig) {
            $this->setMergedConfig(new Config(array(), true));
        }
        if (true === $readOnly) {
            $this->mergedConfig->setReadOnly();
        }
        return $this->mergedConfig;
    }

    /**
     * setMergedConfig 
     * 
     * @param Config $config 
     * @return Manager
     */
    public function setMergedConfig(Config $config)
    {
        $this->mergedConfig = $config;
        return $this;
    }

    /**
     * mergeModuleConfig 
     * 
     * @param mixed $module 
     * @return Manager
     */
    public function mergeModuleConfig($module)
    {
        if ((false === $this->skipConfig)
            && (is_callable(array($module, 'getConfig')))
        ) {
            $this->getMergedConfig(false)->merge($module->getConfig($this->getOptions()->getApplicationEnv()));
        }
        return $this;
    }

    protected function runModuleInit($module)
    {
        if (is_callable(array($module, 'init'))) {
            $module->init($this);
        }
        return $this;
    }

    protected function hasCachedConfig()
    {
        if (($this->getOptions()->getCacheConfig())
            && (file_exists($this->getOptions()->getCacheFilePath()))
        ) {
            return true;
        }
        return false;
    }

    protected function getCachedConfig()
    {
        return new Config(include $this->getOptions()->getCacheFilePath());
    }

    protected function updateCache()
    {
        if (($this->getOptions()->getCacheConfig())
            && (false === $this->skipConfig)
        ) {
            $this->saveConfigCache($this->getMergedConfig());
        }
        return $this;
    }

    protected function saveConfigCache($config)
    {
        $content = "<?php\nreturn " . var_export($config->toArray(), 1) . ';';
        file_put_contents($this->getOptions()->getCacheFilePath(), $content);
        return $this;
    }
}
