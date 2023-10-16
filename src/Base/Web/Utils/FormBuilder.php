<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Utils;

use Apex\App\Base\Web\Utils\FormField;
use Apex\App\Attr\Inject;

/**
 * Form fields creator
 */
class FormBuilder
{

    /**
     * Seperator
     */
    public function seperator(string $label):FormField
    {
        $field = new FormField('seperator');
        return $field->label($label);
    }

    /**
     * Hidden
     */
    public function hidden():FormField
    {
        return new FormField('hidden');
    }

    /**
     * textbox
     */
    public function textbox():FormField
    {
        return new FormField('textbox');
    }

    /**
     * textarea
     */
    public function textarea():FormField
    {
        return new FormField('textarea');
    }

    /**
     * Phone
     */
    public function phone():FormField
    {
        return new FormField('phone');
    }

    /**
     * Amount
     */
    public function amount():FormField
    {
        return new FormField('amount');
    }

    /**
     * Boolean
     */
    public function boolean():FormField
    {
        return new FormField('boolean');
    }

    /**
     * Label
     */
    public function label(string $label = '', string $value = ''):FormField
    {
        $field = new FormField('label');
        return $field->label($label)->value($value);
    }

    /**
     * Select
     */
    public function select():FormField
    {
        return new FormField('select');
    }

    /**
     * checkbox list
     */
    public function checkbox():FormField
    {
        return new FormField('checkbox');
    }

    /**
     * radio list
     */
    public function radio():FormField
    {
        return new FormField('radio');
    }

    /**
     * Date selector
     */
    public function date():FormField
    {
        return new FormField('date_selector');
    }

    /**
     * Time selector
     */
    public function time():FormField
    {
        return new FormField('time_selector');
    }

    /**
     * date interval
     */
    public function dateInterval():FormField
    {
        return new FormField('date_interval');
    }

    /**
     * Submit
     */
    public function submit():FormField
    {
        return new FormField('submit');
    }

    /**
     * Button
     */
    public function button():FormField
    {
        return new FormField('button');
    }

    /**
     * Create or update submit button
     */
    public function createOrUpdateButton(string $name = 'Record', array $attr = []):FormField
    {

        // Initialize
        $field = new FormField('submit');
        $record_id = $attr['record_id'] ?? '';

            // Check if record exists
        if ($record_id == '') {
            $field->value('create')->label("Create New $name");
        } else { 
            $field->value('update')->label("Update $name");
        }

        // Return
        return $field;
    }

    /**
     * One column
     */
    public function onecol(string $contents = ''):FormField
    {
        return $field = new FormField('onecol');
    }

    /**
     * Two column row
     */
    public function twocol(string $contents = ''):FormField
    {
        return $field = new FormField('twocol');
    }

}

