<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Utils;

use Apex\App\Exceptions\ApexFormValidationException;

/**
 * Form field
 */
class FormField
{

    /**
     * Constructor
     */
    public function __construct(
        private string $field,
        private string $value = '',
        private bool $required = false,
        private string $label = '',
        private string $placeholder = '',
        private string $width = '',
        private string $textbox_type = 'text',
        private string $data_type = '',
        private int $min_length = 0,
        private int $max_length = 0,
        private string $href = '',
        private bool $bold = true,
        private string $data_source = '',
        private string $onclick = '',
        private string $onchange = '',
        private string $options = '',
        private string $contents = ''
    ) {

    }

    /**
     * Required
     */
    public function required(bool $required = true):static
    {
        $this->required = $required;
        return $this;
    }

    /**
     * Default value
     */
    public function value(string|int|float|bool $value):static
    {

        // Check for bool
        if (is_bool($value)) { 
            $value = $value === true ? 1 : 0;
        }
        $this->value = (string) $value;
        return $this;
    }

    /**
     * Bold
     */
    public function bold(bool $bold):static
    {

        // Validate
        if ($this->field != 'label') { 
            throw new ApexFormValidationException("The bold() method is only allowed for 'label' fields.");
        }

        // Return
        $this->bold = $bold;
        return $this;
    }

    /**
     * href
     */
    public function href(string $href):static
    {

        // Validate
        if (!in_array($this->field, ['label', 'button'])) {
            throw new ApexFormValidationException("The href() method is only allow for fields of type 'label' and 'button'");
        }

        // Return
        $this->href = $href;
        return $this;
    }

    /**
     * onClick
     */
    public function onClick(string $onclick):static
    {
        $this->onclick = $onclick;
        return $this;
    }

    /**
     * onChange
     */
    public function onChange(string $onchange):static
    {

        // Validate
        if ($this->field != 'select') { 
            throw new ApexFormValidationException("The onChange() method is only allowed for the 'select' field.");
        }

        // Return
        $this->onchange = $onchange;
        return $this;
    }

    /**
     * Width
     */
    public function width(string $width):static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Label
     */
    public function label(string $label):static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Placeholder
     */
    public function placeholder(string $placeholder):static
    {

        // Validate
        if (in_array($this->field, ['boolean', 'select', 'radio', 'checkbox', 'date_selector', 'time_selector'])) { 
            throw new ApexFormValidationException("The form field '$this->field' does not allow placeholders.");
        }

        // Return
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Type
     */
    public function type(string $type):static
    {

        // Validate
        if ($this->field != 'textbox') { 
            throw new ApexFormValidationException("The type() method can only be used on 'textbox' fields.");
        }

        // Return
        $this->textbox_type = $type;
        return $this;
    }

    /**
     * Data tye
     */
    public function dataType(string $type):static
    {

        // Validate
        if ($this->field != 'textbox') { 
            throw new ApexFormValidationException("The dataType() method is only available for 'textbox' fields.");
        } elseif (!in_array($type, ['alphanum', 'integer', 'decimal', 'email', 'url'])) {
            throw new ApexFormValidationException("Invalid data type specified, '$type'.  Supported values are: alphanum, integer, deciaml, email, url");
        }

        // Return
        $this->data_type = $type;
        return $this;
    }

    /**
     * Min length
     */
    public function minLength(int $length):static
    {

        // Validate
        if (!in_array($this->field, ['textbox', 'textarea', 'phone', 'amount'])) { 
            throw new ApexFormValidationException("The minLength() method is only available for the fields: textbox, textarea, phone, amount");
        }

        // Return
        $this->min_length = $length;
        return $this;
    }

    /**
     * Max length
     */
    public function maxLength(int $length):static
    {

        // Validate
        if (!in_array($this->field, ['textbox', 'textarea', 'phone', 'amount'])) { 
            throw new ApexFormValidationException("The maxLength() method is only available for the fields: textbox, textarea, phone, amount");
        }

        // Return
        $this->max_length = $length;
        return $this;
    }

    /**
     * Data source
     */
    public function dataSource(string $data_source):static
    {

        // Validate
        if (!in_array($this->field, ['select','radio','checkbox'])) { 
        throw new ApexFormValidationException("A data source is only allowed on 'select', 'radio' and 'checkbox' fields.");
        }

        // Return
        $this->data_source = $data_source;
        return $this;
    }

    /**
     * Options
     */
    public function options(string $options):static
    {

        // Validate
        if ($this->field != 'select') { 
            throw new ApexFormValidationException("The options() method is only available for 'select' form fields.");
        }

        // Return
        $this->options = $options;
        return $this;
    }

    /**
     * Contents
     */
    public function contents(string $contents):static
    {

        // Validate
        if ($this->field != 'onecol') { 
            throw new ApexFormValidationException("The contents() method is only available for 'onecol' fields.");
        }

        // Return
        $this->contents = $contents;
        return $static;
    }

    /**
     * toArray
     */
    public function toArray():array
    {

        // Start
        $vars = [
            'field' => $this->field,
            'required' => $this->required === true ? 1 : 0
        ];

        // Set string vars
        $string_vars = [
            'label',
            'placeholder',
            'data_type',
            'value',
            'width',
            'data_source',
            'options',
            'contents',
            'href',
            'onclick',
            'onchange'
        ];

        // Basic strings
        foreach ($string_vars as $key) {
            if ($this->$key == '') {
                continue;
            }
            $vars[$key] = $this->$key;
        }

        // Textbox type
        if ($this->field == 'textbox' && $this->textbox_type != 'text') {
            $vars['type'] = $this->textbox_type;
        }

        // Min length
        if ($this->min_length > 0) {
            $vars['minlength'] = $this->min_length;
        }

        // Max length
        if ($this->max_length > 0) { 
            $vars['maxlength'] = $this->max_length;
        }

        // Bold
        if ($this->field == 'label') { 
            $vars['bold'] = $this->bold === true ? 1 : 0;
        }

        // Return
        return $vars;
    }

}


