<?php
namespace Export\Service;

use Export\Exporter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ExporterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $application = $container->get('Application');

        $downloadAction = new Exporter($application);

        return $downloadAction;
    }
}
