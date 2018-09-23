<?php
/**
 * --- WorkSena - Micro Framework ---
 * @license https://github.com/WalderlanSena/worksena/blob/master/LICENSE (MIT License)
 * @copyright © 2013-2018 - @author Walderlan Sena <walderlan@worksena.xyz>
 */

namespace WS\DI;

class Resolver
{
    private $dependencies_inject;

    /**
     * @param $class
     * @param array $dependencies_inject
     * @return object
     * @throws \ReflectionException
     */
    public function resolve($class, $dependencies_inject = [])
    {
        if (!empty($dependencies_inject)) {
            $this->dependencies_inject = $dependencies_inject;
        }

        if (is_string($class)) {
            $class = new \ReflectionClass($class);
        }

        if (!$class->isInstantiable() && !is_object($this->getDependenceInterface($class))) {
            throw new \Exception("{$class->name} Não é instanciavél.");
        }

        if (is_object($this->getDependenceInterface($class))) {
            $class = $this->getDependenceInterface($class);
        }

        $constructor = $class->getConstructor();

        if (is_null($constructor)) {
            return $class->newInstance();
        }

        $parameters = $constructor->getParameters();

        $dependencies = $this->getDependencies($parameters);

        return $class->newInstanceArgs($dependencies);
    }

    /**
     * @param $parameters
     * @return array
     * @throws \ReflectionException
     */
    protected function getDependencies($parameters)
    {
        $dependencies = [];
        /**
         * @var \ReflectionParameter $parameter
         */
        foreach ($parameters as $parameter) {

            $dependency = $parameter->getClass();

            if (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolve($dependency);
            }
        }
        return $dependencies;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return mixed
     * @throws \Exception
     */
    protected function resolveNonClass(\ReflectionParameter $parameter)
    {
        if (isset($this->dependencies_inject[$parameter->name])) {
            return $this->dependencies_inject[$parameter->name];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception('Can not resolve class dependency');
    }

    /**
     * @param $class
     * @return bool|\ReflectionClass
     * @throws \ReflectionException
     */
    private function getDependenceInterface($class)
    {
        $configDependencyInjection = include __DIR__ .'/../../../../config/autoload/module.config.php';

        if (!isset($configDependencyInjection['di'])) {
            throw new \Exception('The dependency definition was not found !');
        }

        foreach ($configDependencyInjection['di'] as $key => $value) {
            if ($value == $class->name) {
                $class = new \ReflectionClass($key);
                return $class;
            }
        }
        return true;
    }
}