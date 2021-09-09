<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Utils;

/**
 * Form fields creator
 */
class FormFieldsCreator
{

    /**
     * Seperator
     */
    public function seperator(string $label):array
    {
        return ['field' => 'seperator', 'label' => $label];
    }

    /**
     * textbox
     */
    public function textbox(string $value = '', string $label = '', bool $is_required = false, string $placeholder = '', string $type = 'text', string $data_type = ''):array
    {

        // Set array
        $vars = [
            'field' => 'textbox', 
            'value' => $value, 
            'label' => $label, 
            'is_required' => $is_required, 
            'placeholder' => $placeholder,  
            'type' => $type, 
            'data_type' => $data_type
        ];

        // Return
        return $vars;
    }

    /**
     * Phone
     */
    public function phone(string $value = '', string $label = ''):array
    {

        // Set vars
        $vars = [
            'field' => 'phone', 
            'value' => $value, 
            'label' => $label
        ];

        // Return
        return $vars;
    }

    /**
     * Select
     */
    public function select(string $value = '', string $data_source = '', bool $required = true, string $label = '', string $options = ''):array
    {

        // Set vars
        $vars = [
            'field' => 'select',
            'value' => $value,
            'required' => $required === true ? 1 : 0,
            'data_source' => $data_source,
            'label' => $label,
            'options' => $options
        ];

        // Return
        return $vars;
    }
    /**
     * Create or update submit button
     */
    public function createOrUpdateButton(string $name = 'Record', array $attr = []):array
    {

            // Check if record exists
        $record_id = $attr['record_id'] ?? '';
        if ($record_id == '') { 
            $field = ['field' => 'submit', 'value' => 'create', 'label' => "Create New $name"];
        } else { 
            $field = ['field' => 'submit', 'value' => 'update', 'label' => "Update $name"];
        }

        // Return
        return $field;
    }

    /**
     * Boolean
     */
    public function boolean(bool $value = true, string $label = ''):array
    {

        $vars = [
            'field' => 'boolean',
            'value' => $value,
            'label' => $label
        ];

        // Return
        return $vars;
    }


}

