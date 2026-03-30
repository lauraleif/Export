<?php
namespace Export\Service\Form;

use Export\Form\ExportButtonForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ExportButtonFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ExportButtonForm(null, $options ?? []);
        $settings = $services->get('Omeka\Settings\Site');

        if (!empty($options["admin"])) {
            $form->availableFormats = array_keys(\Export\Exporter::IMPLEMENTED_FORMATS);
        } else {
            $form->availableFormats = $settings->get('export_enabled_formats', []);
        }

        if (isset($options["browse_page"])) {
            $form->setAttribute('class', 'export-browse');
            $form->browsePage = true;
        }

        return $form;
    }
}
