<?php declare(strict_types=1);

namespace Export\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Export\Form\Element\OptionalMultiCheckbox;

class SiteSettingsFieldset extends Fieldset
{
    /*
     * @var string
     */
    protected $label = 'Export'; // @translate

    public function init(): void
    {
        $this->setLabel($this->label);

        $this->add([
                'name' => 'export_public_button',
                'type' => Element\Radio::class,
                'options' => [
                    'element_group' => 'export',
                    'label' => 'Public export button placement', // @translate
                    'value_options' => [
                        'no' => 'No', // @translate
                        'before' => 'Before the resource', // @translate
                        'after' => 'After the resource', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'export_public_button',
                    'value' => 'no',
                ],
            ]);

        $formats = array_keys(\Export\Exporter::IMPLEMENTED_FORMATS);
        $valueOptions = [];

        foreach ($formats as $format) {
            $valueOptions[] = [
                'label' => $format,
                'value' => $format,
            ];
        }

        $this->add([
                'name' => 'export_enabled_formats',
                'type' => OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'export',
                    'label' => 'Export formats enabled for this site', // @translate
                    'value_options' => $valueOptions,
                ],
                'attributes' => [
                    'id' => 'export_enabled_formats',
                ],
            ]);
    }
}
