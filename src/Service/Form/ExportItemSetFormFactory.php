<?php
namespace Export\Service\Form;

use Export\Form\ExportItemSetForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ExportItemSetFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ExportItemSetForm(null, $options ?? []);

        $form->availableFormats = array_keys(\Export\Exporter::IMPLEMENTED_FORMATS);
        return $form;
    }
}
