<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\Routes;

use Adbar\Dot;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

/**
 * Route mapper based on routes.xml declarations
 * Inspired by https://github.com/magento/magento2/blob/2.4-develop/dev/tests/static/framework/Magento/TestFramework/Dependency/Route/RouteMapper.php
 */
class RouteMapper
{
    private const ROUTER_TYPE_ADMIN = 'admin';
    private const ROUTER_TYPE_STANDARD = 'standard';
    private const ROUTER_TYPES = [self::ROUTER_TYPE_ADMIN, self::ROUTER_TYPE_STANDARD];

    /**
     * List of routers
     *
     * Format: array(
     *  '{Router_Id}' => '{Route_Id}' => ['{Module_Name}']
     * )
     *
     * @var array
     */
    private array $routers = [];

    /**
     * List of routes.xml files by modules
     *
     * Format: array(
     *  '{Module_Name}' => ['{Filename}']
     * )
     *
     * @var array
     */
    private array $routeConfigFiles = [];

    /**
     * List of controllers actions
     *
     * Format: array(
     *  '{Router_Id}' => '{Route_Id}' => '{Controller_Name}' => '{Action_Name}' => [{'Module_Name'}]
     * )
     *
     * @var array
     */
    private array $actions = [];

    private const RESERVED_WORDS = [
        'abstract' => 1,
        'and' => 1,
        'array' => 1,
        'as' => 1,
        'break' => 1,
        'callable' => 1,
        'case' => 1,
        'catch' => 1,
        'class' => 1,
        'clone' => 1,
        'const' => 1,
        'continue' => 1,
        'declare' => 1,
        'default' => 1,
        'die' => 1,
        'do' => 1,
        'echo' => 1,
        'else' => 1,
        'elseif' => 1,
        'empty' => 1,
        'enddeclare' => 1,
        'endfor' => 1,
        'endforeach' => 1,
        'endif' => 1,
        'endswitch' => 1,
        'endwhile' => 1,
        'eval' => 1,
        'exit' => 1,
        'extends' => 1,
        'final' => 1,
        'finally' => 1,
        'fn' => 1,
        'for' => 1,
        'foreach' => 1,
        'function' => 1,
        'global' => 1,
        'goto' => 1,
        'if' => 1,
        'implements' => 1,
        'include' => 1,
        'instanceof' => 1,
        'insteadof' => 1,
        'interface' => 1,
        'isset' => 1,
        'list' => 1,
        'match' => 1,
        'namespace' => 1,
        'new' => 1,
        'or' => 1,
        'print' => 1,
        'private' => 1,
        'protected' => 1,
        'public' => 1,
        'require' => 1,
        'return' => 1,
        'static' => 1,
        'switch' => 1,
        'throw' => 1,
        'trait' => 1,
        'try' => 1,
        'unset' => 1,
        'use' => 1,
        'var' => 1,
        'void' => 1,
        'while' => 1,
        'xor' => 1,
        'yield' => 1
    ]; // TODO: replace with ENUM

    public function __construct(
        private readonly PackagesRegistry     $packagesRegistry,
        private readonly PhpFilesListProvider $filesListProvider
    ) {
    }

    public function getDependencyFromRoutePath(string $path, string $phpFilePath): ?string
    {
        if (str_contains($path, '*')) {
            return $this->processWildcardUrl($path, $phpFilePath);
        } elseif (preg_match('#rest(?<service>/V1/.+)#i', $path, $apiMatch)) {
//                $modules = $this->processApiUrl($apiMatch['service']); TODO
            return null;
        } else {
            return $this->processStandardUrl($path);
        }
    }

    private function processWildcardUrl(string $urlPath, string $filePath): ?string
    {
        $filePath = strtolower($filePath);
        $urlRoutePieces = explode('/', $urlPath);
        $routeId = array_shift($urlRoutePieces);
        //Skip route wildcard processing as this requires using the routeMapper
        if ('*' === $routeId) {
            return null;
        }

        /**
         * Only handle Controllers. ie: Ignore Blocks, Templates, and Models due to complexity in static resolution
         * of route
         */
        if (!preg_match(
            '#controller/(adminhtml/)?(?<controller_name>.+)/(?<action_name>\w+).php$#',
            $filePath,
            $fileParts
        )) {
            return null;
        }

        $controllerName = array_shift($urlRoutePieces);
        if ('*' === $controllerName) {
            $controllerName = str_replace('/', '_', $fileParts['controller_name']);
        }

        if (empty($urlRoutePieces) || !$urlRoutePieces[0]) {
            $actionName = 'index';
        } else {
            $actionName = array_shift($urlRoutePieces);
            if ('*' === $actionName) {
                $actionName = $fileParts['action_name'];
            }
        }

        if (isset(self::RESERVED_WORDS[$actionName])) {
            $actionName .= 'action';
        }

        return $this->getDependency("$routeId/$controllerName/$actionName");
    }


    private function processStandardUrl(string $path): ?string
    {
        $pattern = '#(?<route_id>[a-z0-9\-_]{3,})'
            . '(/(?<controller_name>[a-z0-9\-_]+))?(/(?<action_name>[a-z0-9\-_]+))?#i';
        if (!preg_match($pattern, $path, $match)) {
            return null;
        }
        $routeId = $match['route_id'];
        $controllerName = $match['controller_name'] ?? 'index';
        $actionName = $match['action_name'] ?? 'index';
        if (isset(self::RESERVED_WORDS[$actionName])) {
            $actionName .= 'action';
        }

        return $this->getDependency("$routeId/$controllerName/$actionName");
    }

    private function getDependency(
        string $routePath
    ): ?string {
        $routePath = strtolower($routePath);

        foreach (self::ROUTER_TYPES as $routerId) {
            if ($this->getActionsMap()[$routerId]->has($routePath)) {
                return $this->getActionsMap()[$routerId]->get($routePath);
            }
        }

        return null;
    }

    /**
     * Provide routing declaration
     *
     * @return array
     */
    private function getRoutersMap(): array
    {
        if (empty($this->routers)) {
            foreach ($this->getListRoutesXml() as $module => $configFiles) {
                foreach ($configFiles as $configFile) {
                    $this->processConfigFile($module, $configFile);
                }
            }
        }

        return $this->routers;
    }

    /**
     * Update routers map for the module basing on the routing config file
     *
     * @param string $module
     * @param string $configFile
     *
     * @return void
     */
    private function processConfigFile(string $module, string $configFile): void
    {
        // Read module's routes.xml file
        $config = simplexml_load_file($configFile);

        $routers = $config->xpath("/config/router");
        foreach ($routers as $router) {
            $routerId = (string)$router['id'];
            foreach ($router->xpath('route') as $route) {
                $routeId = (string)$route['id'];
                if (!isset($this->routers[$routerId][$routeId])) {
                    $this->routers[$routerId][$routeId] = [];
                }
                if (!in_array($module, $this->routers[$routerId][$routeId])) {
                    $this->routers[$routerId][$routeId][] = $module;
                }
            }
        }
    }

    /**
     * Prepare the list of routes.xml files (by modules)
     */
    private function getListRoutesXml(): array
    {
        if (empty($this->routeConfigFiles)) {
            $packages = $this->packagesRegistry->getAllPackagesExcludingDev();
            foreach ($packages as $package) {
                $packageName = $package->getName();
                foreach ($package->getConfig()->getRoutesXml() as $routeFile) {
                    $this->routeConfigFiles[$packageName][] = $routeFile->getPathname();
                }

            }
        }

        return $this->routeConfigFiles;
    }

    /**
     * @return array<string, Dot>
     */
    private function getActionsMap(): array
    {
        if (empty($this->actions)) {
            $files = $this->filesListProvider->getPhpFiles();
            $actionsMap = [];
            foreach ($this->getRoutersMap() as $routerId => $routes) {
                $actionsMapPerArea = [];
                foreach ($routes as $routeId => $dependencies) {
                    foreach ($dependencies as $packageName) {
                        if (empty($files[$packageName])) {
                            continue;
                        }
                        $actionsMapPerArea[$routeId] = $actionsMapPerArea[$routeId] ?? [];
                        $this->setModuleActionsMapping(
                            actions: $actionsMapPerArea[$routeId],
                            module: $packageName,
                            routerId: $routerId,
                            files: $files[$packageName]
                        );
                    }
                }
                $actionsMap[$routerId] = new Dot($actionsMapPerArea, delimiter: '/');
            }
            $this->actions = $actionsMap;
        }

        return $this->actions;
    }

    private function setModuleActionsMapping(array &$actions, string $module, string $routerId, array $files): void
    {
        $packagePath = $this->packagesRegistry->getPackage($module)->getPath();

        $controllersDirPattern = sprintf(
            "%s/Controller/%s",
            $packagePath,
            $routerId === self::ROUTER_TYPE_ADMIN ? 'Adminhtml/' : '(?!Adminhtml)'
        );


        $actionsPattern = sprintf("#%s(?<controller>\\S+)/(?<action_name>\\w+)\\.php\$#", $controllersDirPattern);

        foreach ($files as $controllerAction) {
            if (preg_match($actionsPattern, $controllerAction, $matches)) {
                $controllerName = strtolower(str_replace('/', '_', $matches['controller']));
                $actionName = strtolower($matches['action_name']);
                $actions[$controllerName][$actionName] = $module;
            }
        }
    }
}
