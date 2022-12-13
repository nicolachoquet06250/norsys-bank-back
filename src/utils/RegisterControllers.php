<?php

namespace NorsysBank\utils;

use NorsysBank\attributes\Controller;
use NorsysBank\attributes\Route;
use NorsysBank\enums\HttpMethod;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class RegisterControllers {
    private array $composerConf = [];

    private function __construct(
        private string $controllersPath
    )
    {
        $composer = file_get_contents(__ROOT__.'/composer.json');
        $this->composerConf = json_decode($composer, true)['autoload']['psr-4'];
    }

    public static function instantiate(string $controllersPath): self
    {
        return new RegisterControllers($controllersPath);
    }

    public function register()
    {
        $completeNamespace = str_replace(__ROOT__.'/', '', realpath($this->controllersPath));
        foreach ($this->composerConf as $namespace => $path) {
            $completeNamespace = str_replace($path, $namespace, $completeNamespace);
        }

        if ($dir = opendir($this->controllersPath)) {
            while (false !== ($entry = readdir($dir))) {
                if (is_file($this->controllersPath . '/' . $entry)) {
                    $className = $completeNamespace . '\\' . str_replace('.php', '', $entry);

                    if (class_exists($className)) {
                        $classReflection = new ReflectionClass($className);
                        $classAttributes = $classReflection->getAttributes();
                        $hasClassAttributes = count($classAttributes) > 0;

                        $hasControllerAttribute = count(array_filter($classAttributes, fn(ReflectionAttribute $attr) => $attr->getName() === Controller::class)) > 0;

                        $baseUrl = '/';

                        if ($hasClassAttributes && $hasControllerAttribute) {
                            $routesAttributes = $classReflection->getAttributes(Route::class);

                            foreach ($routesAttributes as $routeAttribute) {
                                /**
                                 * @var Route $attr
                                 */
                                $attr = $routeAttribute->newInstance();
                                $attr->setTarget($className);

                                if (is_null($attr->getUrl())) {
                                    preg_match_all('/[A-Z][^A-Z]+/', str_replace('.php', '', $entry), $matches, PREG_SET_ORDER, 0);
                                    $baseUrl = '/' . implode('-', array_map(fn(string $item) => strtolower($item), flatten($matches)));
                                    
                                    $attr->setUrl($baseUrl);
                                }
                            }
                        }

                        $classMethods = array_filter(
                            $classReflection->getMethods(ReflectionMethod::IS_PUBLIC), 
                            fn(ReflectionMethod $m) => $m->getName() !== '__construct'
                        );
                        $hasClassMethods = count($classMethods) > 0;

                        if ($hasClassMethods) {
                            foreach ($classMethods as $method) {
                                $methodName = $method->getName();
                                $methodAttributes = $method->getAttributes(Route::class);
                                $hasMethodAttributes = count($methodAttributes) > 0;

                                if ($hasMethodAttributes) {
                                    foreach ($methodAttributes as $methodAttribute) {
                                        /**
                                         * @var Route $attr
                                         */
                                        $attr = $methodAttribute->newInstance();
                                        $attr->setTarget($className);
                                        $attr->setMethod($methodName);

                                        if (is_null($attr->getUrl())) {
                                            preg_match_all('/[A-Z]?[^A-Z]+/', $methodName, $matches, PREG_SET_ORDER, 0);
                                            $url = '/' . implode('-', array_map(fn(string $item) => strtolower($item), flatten($matches)));

                                            $attr->setUrl($url);
                                        }

                                        Router::instantiate()->route(url: $baseUrl . $attr->getUrl(), callback: function() use ($className, $methodName, $attr) {
                                            $ctrl = new $className($_GET);
                                            return $ctrl->{$methodName}();
                                        }, httpMethod: $attr->getHttpMethod());
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}