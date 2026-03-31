<?php
namespace Export\Form;

use Laminas\Form\Form;

class ExportButtonForm extends Form
{
    /**
     * @var array[string]
     */
    public $availableFormats = [];

    /**
     * @var bool
     */
    public $browsePage = false;

    public function init()
    {
        $this->setAttribute('id', 'export-button-form');
        $this->availableFormats = array_combine($this->availableFormats, $this->availableFormats);

        $this->add([
                    'name' => 'controller',
                    'type' => 'text',
                    'attributes' => [
                        'hidden' => true,
                    ],
        ]);

        $this->add([
                    'name' => 'format_name',
                    'type' => 'Select',
                    'options' => [
                        'label' => 'Select format to export', // @translate
                        'value_options' => $this->availableFormats,
                    ],
                    'attributes' => [
                        'required' => true,
                    ],
        ]);

        if ($this->browsePage) {
            $this->get('format_name')->setOption('info', 'Check the resources you want to export. If none are selected, an export job will be performed to export all resources.'); // @translate
        }
    }
}
