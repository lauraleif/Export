<?php
namespace Export\Form;

use Omeka\Form\Element\ItemSetSelect;
use Laminas\Form\Form;

class ExportItemSetForm extends Form
{
    /**
     * @var array[string]
     */
    public $availableFormats = [];

    public function init()
    {
        $this->add([
                    'name' => 'item_set',
                    'type' => ItemSetSelect::class,
                    'attributes' => [
                        'id' => 'select-item-set',
                        'class' => 'chosen-select',
                        'multiple' => false,
                        'data-placeholder' => 'Select item sets', // @translate
                    ],
                    'options' => [
                        'label' => 'Select item set to export', // @translate
                        'resource_value_options' => [
                            'resource' => 'item_sets',
                            'query' => [],
                        ],
                    ],
        ]);

        $this->availableFormats = array_combine($this->availableFormats, $this->availableFormats);

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
    }
}
