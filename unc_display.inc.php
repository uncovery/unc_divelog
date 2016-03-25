<?php

function unc_display_shortcode($atts = array()) {
    global $UNC_DIVELOG;

    unc_divelog_display_init($atts);

    $D = $UNC_DIVELOG['display'];

    $avail_dives = unc_divelog_enumerate_dives($D['data_format'], $D['source']);

    $out = '';
    if ($D['date_selector'] == 'calendar') {
        $avail_dives = unc_divelog_enumerate_dives($D['data_format']);
        $out = "\n     <script type=\"text/javascript\">
        var availableDates = [\"" . implode("\",\"", array_keys($avail_dives)) . "\"];
        var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
        jQuery(document).ready(function($) {
            datepicker_ready('{$D['date']}');
        });
        </script>";
        $out .= "Date: <input type=\"text\" id=\"datepicker\" value=\"{$D['date']}\">";
    } else if ($D['date_selector'] == 'datelist') {
        $avail_dives = unc_divelog_enumerate_dives($D['data_format']);
        $out = "<select id=\"datepicker\" onchange=\"datelist_change()\">\n";
        foreach ($avail_dives as $folder_date => $dive_count) {
            $out .= "<option value=\"$folder_date\">$folder_date ($dive_count dives)</option>\n";
        }
        $out .="</select>\n";
    }
    return unc_display_final($out);
}

function unc_divelog_display_init($atts) {
    global $UNC_DIVELOG;
    $a = shortcode_atts( array(
        'options' => false, // we cannot set it to an array here
        'date' => false, // time of the day when we start displaying this date
        'dive' => 83, // time of the day when we stop displaying this date
        'data_set' => false, // description for the whole day
        'offset' => false,
        'data_format' => 'D4i',
        'date_offset' => false,
        'date_selector' => false,
        'source' => 'user.db',
    ), $atts);

    // there can be several options, separated by space
    if (!$a['options']) {
        $options = array();
    } else {
        $options = explode(" ", $a['options']);
    }
    $UNC_DIVELOG['options'] = $options;

    $UNC_DIVELOG['display']['source'] = $a['source'];

    // which dive ID
    $UNC_DIVELOG['display']['dive'] = $a['dive'];

    // data format of that dive
    $UNC_DIVELOG['display']['data_format'] = $a['data_format'];

    $UNC_DIVELOG['display']['date_selector'] = false;
    if (in_array('calendar', $options)) {
        $UNC_DIVELOG['display']['date_selector'] = 'calendar';
    } else if (in_array('datelist', $options)) {
        $UNC_DIVELOG['display']['date_selector'] = 'datelist';
    }
    $UNC_DIVELOG['display']['date'] = unc_divelog_dive_latest();
}


function unc_display_final($out) {
    global $UNC_DIVELOG;
    $D = $UNC_DIVELOG;
    $data = unc_divelog_query();
    $chart_data = $data['dive_path'];
    $start_time = $data['start_time'];
    echo $start_time;
    $date_obj = new DateTime($start_time);
    $final_data = array();
    foreach ($chart_data as $dive_point) {
        if ($dive_point['time'] > 0 ) {
            $date_obj->add(new DateInterval("PT" . $dive_point['time'] . "S"));
        }
        $time = $date_obj->format("H:i:s");
        $final_data[$time] = array(
            'depth' => ($dive_point['depth'] * -1),
            'temperature' => $dive_point['temp'],
            // 'customBullet' => unc_display_gallery_link($time, $dive_point['time']),
        );
    }

    $out .= unc_divelog_javachart($final_data, 'Time', 'none', array('depth' => 'left', 'temperature' => 'right'), 'amchart', false, 500);
    return $out;
}

function unc_display_gallery_link($time, $gap){


    $url = '';
    return '"customBullet": "'. $url .'"';
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
            \"customBulletField\": \"customBullet\",
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