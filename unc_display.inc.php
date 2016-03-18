<?php

function unc_display_shortcode($atts = array()) {
    $a = shortcode_atts( array(
        'options' => false, // we cannot set it to an array here
        'date' => false, // time of the day when we start displaying this date
        'dive' => 83, // time of the day when we stop displaying this date
        'data_set' => false, // description for the whole day
        'offset' => false,
        'data_format' => 'D4i',
        'date_offset' => false,
    ), $atts);

    $out = unc_divelog_query($a['dive'], $a['data_format']);
    return unc_display_final($out);
}


function unc_display_final($data) {
    $chart_data = $data['dive_path'];
    $start_time = $data['start_time'];
    echo $start_time;
    $date_obj = new DateTime($start_time);
    $final_data = array();
    foreach ($chart_data as $id => $dive_point) {
        if ($dive_point['time'] > 0 ) {
            $modifier = "+" . $dive_point['time'] . " second";
            $date_obj->add(new DateInterval("PT" . $dive_point['time'] . "S"));
        }
        $time = $date_obj->format("H:m:s");
        $final_data["$time ($modifier)"] = array('depth' => ($dive_point['depth'] * -1), 'temperature' => $dive_point['temp']);
    }

    return unc_divelog_javachart($final_data, 'Time', 'none', array('depth' => 'left', 'temperature' => 'right'), 'amchart', false, 500);
}


/**
 * Generic 2D Chart generator. Supports multiple axis
 *
 * @global type $UMC_DOMAIN
 * @param array $data as in array('Jan 2016' => array('row1' => 1, 'row2' => 2), ..) ;
 * @param string $y_axis_name name for the Y-axis
 * @param string $stacktype any of "none", "regular", "100%", "3d".
 * @param array $axis_groups as in array('row1' => 'left', 'row2' => 'right') or false
 * @param string $name to name the whole chart. Needed when we have several in one page.
 * @param bool $sum Do we should the sum of all items on the top?
 * @param int $hight pixel height of the chart
 * @return string
 */
function unc_divelog_javachart($data, $y_axis_name, $stacktype, $axis_groups = false, $name = 'amchart', $sum = true, $height = 500) {
    // check the stack type
    $valid_stacktypes = array("none", "regular", "100%", "3d");
    if (!in_array($stacktype, $valid_stacktypes)) {
        return 'Invalid stacktype!';
    }
    $out = "\n<div style=\"width: 100%; height: {$height}px; font-size: 11px;\" id=\"$name\"></div>\n";
    $out .= "<script type=\"text/javascript\">
        var chart = AmCharts.makeChart(\"$name\", {"
        . '
        "type": "serial",
        "theme": "none",
        "marginRight":30,' . "\n";
    if ($sum) {
        $out .= '"legend": {
            "equalWidths": false,
            "periodValueText": "total: [[value.sum]]",
            "position": "top",
            "valueAlign": "left",
            "valueWidth": 100
        },' . "\n";
    }
    $out .= '"dataProvider": ['. "\n";

    $graphs = array();
    foreach ($data as $row => $line) {
        $out .= "{";
        $out .= "\"$y_axis_name\": \"$row\",";
        foreach ($line as $field => $value) {
            $graphs[$field] = ucwords($field);
            $out .= " \"$field\": $value,";
        }
        $out .= "},\n";
    }
    $out .='],
        "valueAxes": [{
            "stackType": "'.$stacktype.'",
            "gridAlpha": 0.07,
            "position": "left",
            "title": "Amount"
        }],
        "graphs": [' ."\n";
    $valaxis = '';
    foreach ($graphs as $graph => $title) {
        $graphaxis = '';
        if ($axis_groups) {
            if (isset($axis_groups[$graph])) {
                $valaxis .= '{"id": "'.$graph.'", "title": "'.$title.'", "position": "'.$axis_groups[$graph].'"},';
                $graphaxis = ',"valueAxis": "'.$graph.'",';
            }
        }
        $out .= "{
            \"title\": \"$title\",
            \"valueField\": \"$graph\",
            \"fillAlphas\": 0.6,
            \"balloonText\": \"$title: [[value]]\"
            $graphaxis},\n";
    }
    $out .= '
        ],
        "plotAreaBorderAlpha": 0,
        "marginTop": 10,
        "marginLeft": 0,
        "marginBottom": 0,
        "chartScrollbar": {"dragIconHeight": 15, "scrollbarHeight": 10},
        "chartCursor": {
            "cursorAlpha": 0
        },
        "categoryField": "'.$y_axis_name.'",
        "categoryAxis": {
            "startOnAxis": true,
            "axisColor": "#DADADA",
            "gridAlpha": 0.07,
            "title": "'.ucwords($y_axis_name).'",
        },' . "\n";
    if ($axis_groups) {
        $out .= "\"valueAxes\": [$valaxis],\n";
    }
    $out .= '
        "export": {
            "enabled": true
        }
    });
</script>' . "\n";
    return $out;
}