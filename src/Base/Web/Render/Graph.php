<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Render;

use Apex\Svc\View;
use Apex\App\Base\Web\Components;
use Apex\App\Base\Web\Utils\GraphUtils;
use Apex\Syrus\Parser\StackElement;
use Apex\App\Attr\Inject;

/**
 * Graph
 */
class Graph
{

    #[Inject(Components::class)]
    private Components $components;

    #[Inject(View::class)]
    private View $view;

    #[Inject(GraphUtils::class)]
    private GraphUtils $graph_utils;

    // Properties
    public array $graph_data = [];
    public array $labels = [];
    public string $border_color = 'white';
    public string $border_width = '0';
    public array $background_colors = ["#ffeb3b", "#63FF84", "#84FF63", "#8463FF", "#6384FF", "#ff5722", "#673ab7"];

    /**
     * Render
     */
    public function render(StackElement $e):string
    {

        // Initial checks
        if (!$alias = $e->getAttr('graph')) {
            return "<b>ERROR:</b> No 'graph' attribute was included within the &lt;a:function&gt; tag.";
        } elseif (!$graph = $this->components->load('Graph', $alias)) {
            return "<b>ERROR:</b> No graph exists with the component alias $data[graph]";
        }

        // Get data
        $graph->getData($e->getAttrAll());

        // Set variables
        $graph_id = $e->getAttr('id') ?? str_replace(':', '_', $e->getAttr('graph'));
        $show_periods = $e->getAttr('show_periods') ?? 0;

        // Create data sets
        $data_sets = [];
        foreach ($graph->graph_data as $set_name => $points) { 
            $set_html = "        {\n";
            $set_html .= "            label: '$set_name', \n";
            $set_html .= "            borderColor: '~border_color~', \n";
            $set_html .= "            borderWidth: '~border_width~', \n";
            $set_html .= "            backgroundColor: [\n";
            $set_html .= "                ~background_colors~\n";
            $set_html .= "            ], \n";
            $set_html .= "            data: [" . implode(", ", $points) . "]\n";
            $set_html .= "        }";
            $data_sets[] = $set_html;
        }

        // Set Javascript vars
        $js_vars = [
            '~graph_id~' => $graph_id,
            '~data_sets~' => implode(", \n            ", $data_sets),  
            '~graph_type~' => $graph->type ?? 'bar', 
            '~labels~' => "'" . implode("','", $graph->labels) . "'", 
            '~background_colors~' => "'" . implode("',\n                '", $graph->background_colors) . "'", 
            '~border_color~' => $graph->border_color, 
            '~border_width~' => $graph->border_width 
        ];

        // Get and add Javascript code to view
        $js_code = base64_decode('CnZhciBjdHggPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnfmdyYXBoX2lkficpLmdldENvbnRleHQoJzJkJyk7CnZhciBjaGFydCA9IG5ldyBDaGFydChjdHgsIHsKICAgIHR5cGU6ICd+Z3JhcGhfdHlwZX4nLAogICAgZGF0YTogewogICAgICAgIGxhYmVsczogW35sYWJlbHN+XSwKICAgICAgICBkYXRhc2V0czogWwogICAgICAgICAgICB+ZGF0YV9zZXRzfgogICAgICAgIF0KICAgIH0sCiAgICBvcHRpb25zOiB7fQp9KTsKCgoK');
        foreach ($js_vars as $key => $value) { 
            $js_code = str_replace($key, $value, $js_code);
        }
        $this->view->addJavascript(strtr($js_code, $js_vars));

        // Add periods HTML, if needed
        $html = '';
        if ($show_periods == 1) { 
            $html .= $this->graph_utils->getPeriodsHtml();
        }

        // Get period html
        $period_html = $this->graph_utils->getPeriodsHtml();
        $this->view->assign('periods_html', $period_html);

        // Add canvas, and return
        $html .= "<canvas id=\"$graph_id\"></canvas>";
        return $html;
    }

    /**
     * Add data set
     */
    final public function addDataset(array $data, string $label = ''):void
    {

        // Add to data
        if ($label == '') { 
            $this->graph_data[] = $data;
        } else { 
            $this->graph_data[$label] = $data;
        }

    }

    /**
     * Set labels
     */
    public function setLabels(array $labels):void
    {
        $this->labels = $labels;
    }

    /**
     * Add label
     */
    public function addLabel(string $label):void
    {
        $this->labels[] = $label;
    }

}

