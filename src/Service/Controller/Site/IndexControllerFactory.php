<?php
namespace Export\Service\Controller\Site;

use Export\Controller\Site\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $indexController = new IndexController($serviceLocator);
        return $indexController;
    }
}
