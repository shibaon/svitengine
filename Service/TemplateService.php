<?php

namespace Svi\TengineBundle\Service;

use Svi\AppContainer;
use Svi\Application;
use Svi\TengineBundle\Twig\SviExtension;

class TemplateService extends AppContainer
{
    const ENGINE_TWIG = 'twig';

    private $processors = [];
    private $onLoads = [];

    public function __construct(Application $app)
    {
        parent::__construct($app);

        if ($this->app->getConfigService()->get('twig')) {
            $this->app['twig'] = function () {
                $loader = new \Twig_Loader_Filesystem([
                    $this->app->getRootDir() . '/src',
                ]);
                $loader->addPath($this->app->getRootDir() . '/vendor');
                $twig = new \Twig_Environment($loader, [
                        'cache' => $this->app['debug'] ? false : $this->app->getRootDir() . '/app/cache',
                    ] + $this->app->getConfigService()->get('twig'));
                $twig->addExtension(new SviExtension($this->app));
                $twig->addExtension(new \Twig_Extension_Debug());
                if ($this->app['debug']) {
                    $twig->enableDebug();
                }
                $this->engineLoaded($twig);

                return $twig;
            };
            $this->addEngine('twig', 'twig');
        }
    }

    public function addEngine($type, $instance)
    {
        $this->processors[$type] = $instance;
    }

    public function getEngine($type)
    {
        if (!array_key_exists($type, $this->processors)) {
            throw new \Exception('There is no registered "' . $type . '" template processor');
        }

        return $this->app[$this->processors[$type]];
    }

    public function render($templateName, array $context = [], $processorType = null)
    {
        if (!$processorType) {
            $processorTypes = array_keys($this->processors);
            if (!array_key_exists(0, $processorTypes)) {
                throw new \Exception('There are no any template processors configured');
            }
            $processorType = $processorTypes[0];
        }

        switch ($processorType) {
            case self::ENGINE_TWIG:
                /** Removing .twig extension if that was in $templateName, because we do not ask template extension
                 * by default
                 **/
                $templateName = preg_replace("/(\\." . self::ENGINE_TWIG . ')$/', '', $templateName);
                return $this->getTwig()->render($templateName . '.' . self::ENGINE_TWIG, $context);
            default:
                throw new \Exception('There are no any template processors configured');
        }
    }

    /**
     * @return bool
     */
    public function hasTwig()
    {
        return array_key_exists('twig', $this->processors);
    }

    /**
     * @return \Twig_Environment
     * @throws \Exception
     */
    public function getTwig()
    {
        if (!array_key_exists('twig', $this->processors)) {
            throw new \Exception('Twig template processor is not configured');
        }

        return $this->getEngine('twig');
    }

    public function onEngineLoad($callback)
    {
        $this->onLoads[] = $callback;
    }

    public function addLoadPath($path)
    {
        if (!isset($this->app['TengineLoadPath.' . $path])) {
            if (isset($this->app['twig']) && $this->app['twig']) {
                /** @var \Twig_Loader_Filesystem $loader */
                $loader = $this->app['twig']->getLoader();
                $loader->addPath($path);
                $this->app['TengineLoadPath.' . $path] = true;
            }
        }
    }

    protected function engineLoaded($engine)
    {
        foreach ($this->onLoads as $handler) {
            $reflection = new \ReflectionFunction($handler);
            $class = $reflection->getParameters()[0]->getClass();
            if ($class->isInstance($engine)) {
                $handler($engine);
            }
        }
    }

}