<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Utils;

use Apex\Svc\{App, View};
use Apex\App\Base\Web\Components;
use Apex\App\Base\Web\Utils;
use Apex\App\Exceptions\ApexFormValidationException;
use Apex\App\Attr\Inject;

/**
 * Form validate
 */
class FormValidator
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(View::class)]
    private View $view;

    #[Inject(Components::class)]
    private Components $components;

    /**
     * Validate a form components
     */
    public function validateForm(string $form_alias, array $attr = [], string $error_type = 'view'):bool
    {

        // Load components
        if (!$form = $this->components->load('form', $form_alias, $attr)) { 
            throw new ApexFormValidationException("Unable to load the form with alias, $form_alias");
        }

        // Get fields
        $fields = $form->getFields($attr);
        list($required, $data_types, $min_length, $max_length) = [[], [], [], []];

        // Compile fields
        foreach ($fields as $name => $vars) { 

            // Check for FormField instance
            if ($vars instanceof FormField) {
                $vars = $vars->toArray();
            }

            // Check required
            if (isset($vars['required']) && $vars['required'] == 1) {
                $required[] = $name;
            }

            // Check data type
            if (isset($vars['data_type']) && $vars['data_type'] != '') { 
                $data_types[$name] = $vars['data_type'];
            }

            // Min length
            if (isset($vars['minlength']) && $vars['minlength'] != '') { 
                $min_length[$name] = $vars['minlength'];
            }

            // Max length
            if (isset($vars['maxlength']) && $vars['maxlength'] != '') {
                $max_length[$name] = $vars['maxlength'];
            }

        }

        // Validate fields
        $this->validateFields($error_type, $required, $data_types, $min_length, $max_length);

        // Form specific validation
        $form->validate($attr);

        // Return
        return $this->view->hasErrors() === true ? false : true;
    }

    /**
     * Validate form fields
     */
    public function validateFields(
        string $error_type = 'view',
        array $required = [],
        array $data_types = [],
        array $min_length = [],
        array $max_length = [],
        array $labels = []
    ):bool {

        // Check required fields
        $this->checkRequired($required, $error_type);

        // Check data types
        $this->checkDataTypes($data_types, $error_type);

        // Min length
        $this->checkMinLength($min_length, $error_type);

        // Max length
        $this->checkMaxLength($max_length, $error_type);

        // Return
        return $this->view->hasErrors() === true ? false : true;
    }

    /**
     * Check required
     */
    public function checkRequired(array $required, string $error_type = 'view'):void
    {

        foreach ($required as $var) { 
            $value = $this->app->post($var);
            if ($value !== null && $value != '') {
                continue;
            }

            // Give error
            $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));
            if ($error_type == 'view') { 
                $this->view->addCallout(tr("The form field %s was left blank, and is required", $label), 'error'); 
            } else { 
                throw new ApexFormValidationException(tr("The form field %s was left blank, and is required", $label));
            }
        }

    }

    /**
     * Check data types
     */
    public function checkDataTypes(array $data_types, string $error_type = 'view'):void
    {

        // Check data types
        foreach ($data_types as $var => $type) { 

            // Set variables
            $errmsg = '';
            $value = $this->app->post($var);
            $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));

            // Check type
            if ($type == 'alphanum' && !preg_match('/^[a-zA-Z]+[a-zA-Z0-9\._-]+$/', $value)) { 
                $errmsg = "The form field %s must be alpha-numeric, and can not contain spaces or special characters.";

            } elseif ($type == 'integer' && preg_match("/\D/", (string) $value)) { 
                $errmsg = "The form field %s must be an integer only.";

            } elseif ($type == 'decimal' && $value != '' && !preg_match("/^[0-9]+(\.[0-9]{1,8})?$/", (string) $value)) { 
                $errmsg = "The form field %s can only be a decimal / amount.";

            } elseif ($type == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) { 
                $errmsg = "The form field %s must be a valid e-mail address.";

            } elseif ($type == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) { 
                $errmsg = "The form field %s must be a valid URL.";
            }

            // Continue if no error
            if ($errmsg == '') {
                continue;
            }

            // Give error
            if ($error_type == 'view') { 
                $this->view->addCallout(tr($errmsg, $label), 'error'); 
            } else { 
                throw new ApexFormValidationException($errmsg, $label); 
            }
        }

    }

    /**
     * Check min length
     */
    public function checkMinLength(array $min_length, string $error_type = 'view'):void
    {

        // Check fields
        foreach ($min_length as $var => $length) { 
            $value = $this->app->post($var);
            if (strlen($value) >= $length) {
                continue;
            }

            // Get error
            $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));
            $errmsg = tr("The form field %s must be a minimum of %i characters in length.", $label, $length);

            // Set error
            if ($error_type == 'view') { 
                $this->view->addCallout($errmsg, 'error'); 
            } else {
                throw new ApexFormValidationException($errmsg); 
            }
        }

    }

    /**
     * Max length
     */
    public function checkMaxLength(array $max_length, string $error_type = 'view'):void
    {

        // Check fields
        foreach ($max_length as $var => $length) { 
            $value = $this->app->post($var);
            if ($length > strlen($value)) {
                continue;
            }

            // Get error message
            $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));
            $errmsg = tr("The form field %s can not exceed a maximum of %i characters.", $label, $length);

            // Give error
            if ($error_type == 'view') { 
                $this->view->addCallout($errmsg, 'error');
            } else { 
                throw new ApexFormValidationException($errmsg);
            }
        }

    }

}


