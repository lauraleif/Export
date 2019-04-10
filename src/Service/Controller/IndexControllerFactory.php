<?php
namespace Export\Service\Controller;

use Export\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $api = $serviceLocator->get('Omeka\ApiManager');
        $config = $serviceLocator->get('Config');
        $userSettings = $serviceLocator->get('Omeka\Settings\User');
        $indexController = new IndexController($config, $api, $userSettings, $serviceLocator);
        return $indexController;
    }
}
