<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Utils;

use Apex\App\Sys\Utils\Io;

/**
 * Documentation Generator
 */
class DocsGenerator
{

    #[Inject(Io::class)]
    private Io $io;

    /**
     * Generate class
     */
    public function generateClass(string $class_name, string $dest_dir):void
    {

        // Create dest dir
        $dest_dir = rtrim($dest_dir, '/');
        $this->io->createBlankDir($dest_dir);

        // Load class
        $obj = new \ReflectionClass($class_name);
        $list_html = "\n<h1>$class_name</h1>\n\n";
        $list_html .= "<s:docs_function_list>\n";
        $list_html .= "    <s:desc>" . $this->getDescription($obj) . "</s:desc>\n\n";

        // Go through methods
        $method_list = [];
        foreach ($obj->getMethods() as $method) { 
            $this->generateMethod($method, $dest_dir);

            // Get visibility
            $visibility = match(true) {
                $method->isPrivate() => 'private',
                $method->isProtected() => 'protected',
                default => 'public'
            };

            // Add to function list
            $is_static = $method->isStatic() === true ? 1 : 0;
            $method_list[$method->getName()] = "    <s:method name=\"" . $method->getName() . "\" is_static=\"$is_static\" visibility=\"$visibility\" desc=\"" . $this->getDescription($method) . "\">";
        }

        // Sort and add methods to list html
        ksort($method_list);
        foreach ($method_list as $key => $line) {
            $list_html .= "$line\n";
        }

        // Save html
        $list_html .= "</s:docs_function_list>\n\n";
        file_put_contents("$dest_dir/index.html", $list_html);
    }

    /**
     * generate method
     */
    public function generateMethod(\ReflectionMethod $method, string $dest_dir):void
    {

        // Get class
        $obj = $method->getDeclaringClass();

        // Start usage line
        $return_type = $this->getTypeName($method->getReturnType()) . ' ';
        $usage = $return_type;
        $usage .= $obj->getShortName() . '::' . $method->getName() . '(';

        // Start HTML
        $html = "\n<h1>" . $obj->getShortName() . "::" . $method->getName() . "</h1>\n\n"; 
        $html .= "<s:docs_function>\n\n";

        // GO through parameters
        list($params_html, $sig_params) = ['', []];
        foreach ($method->getParameters() as $param) {
            $sig_params[] = $this->getParamSignature($param);
            $required = $param->isOptional() === true ? 0 : 1;
            $params_html .= "    <s:param name=\"" . $param->getName() . "\" required=\"$required\" type=\"" . $this->getTypeName($param->getType()) . "\" desc=\"\">\n"; 
        }
        $usage .= implode(', ', $sig_params) . ')';

        // Add to HTML
        $html .= "    <s:usage>$usage</s:usage>\n";
        $html .= "    <s:desc>" . $this->getDescription($method) . "</s:desc>\n\n";
        $html .= "$params_html\n";
        $html .= "    <s:return>$return_type</s:return>\n\n";
        $html .= "</s:docs_function>\n\n";

        // Save file
        $file = $dest_dir . '/' . strtolower($method->getName()) . '.html';
        file_put_contents($file, $html);
    }

    /**
     * Get param signature
     */
    private function getParamSignature(\ReflectionParameter $param):string
    {

        // Get type
        if ($param->isVariadic() === true) { 
            $type = '...';
        } else { 
            $type = $this->getTypeName($param->getType());
        }

        // Start sig
        $sig = $type . ' $' . $param->getName();

        // Add default value
        if ($param->isDefaultValueAvailable() === true) {
            $def = $param->getDefaultValue();
            if (is_bool($def) && $def === true) {
                $def = 'true';
            } elseif (is_bool($def) && $def === false) {
                $def = 'false';
            } elseif ($def === null) {
                $def = 'null';
            } else {
                $def = is_array($def) ? '[]' : "'" . $def . "'";
            }
            $sig .= " = " . $def;
        }

        // Check is optional
        if ($param->isOptional() === true) {
            $sig = '[ ' . $sig . ' ]';
        }

        // Return
        return $sig;
    }

    /**
     * Get type name
     */
    private function getTypeName(?\ReflectionType $type):string
    {

        // Check for null
        if ($type === null) { 
            return '';
        }

        // Get name
        if (method_exists($type, 'getTypes')) {

            $names = [];
            foreach ($type->getTypes() as $t) {
                $names[] = $this->getTypeName($t);
            }
            return implode(' | ', $names);

        } else {
            $name = $type->getName();
        }
        if ($type->isBuiltin() === false) {
            $parts = explode("\\", $name);
            $name = array_pop($parts);
        }

        // Check for null
        if ($type->allowsNull() === true) {
            $name = '?' . $name;
        }

        // Return
        return $name;
    }

    /**
     * Get method description
     */
    public function getDescription(\ReflectionClass | \ReflectionMethod $obj):string
    {

        // Get doc comment
        if (!$doc = $obj->getDocComment()) {
            return '';
        }
        $lines = explode("\n", $doc);

        // Go through lines
        $desc = '';
        foreach ($lines as $line) {
            if (in_array(trim($line), ['', '/**', '*/'])) {
                continue;
            }
            $line = trim(trim($line), '*');
            $desc .= trim($line) . ' ';
        }

        // Return
        return $desc;
    }

}


