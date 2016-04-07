<?php

function uncd_display_ajax_datepicker() {
    $dive_id = filter_input(INPUT_GET, 'dive_id', FILTER_SANITIZE_NUMBER_INT);
    XMPP_ERROR_send_msg($dive_id);
    uncd_display_shortcode(array('dive_id' => $dive_id, 'echo' => true));
}

function uncd_display_shortcode($atts = array()) {
    global $UNC_DIVELOG;
    uncd_divelog_display_init($atts);

    $D = $UNC_DIVELOG['display'];

    $out = '';
    if ($D['date_selector'] == 'calendar') {
        $avail_dives = uncd_divelog_enumerate_dives($D['data_format']);
        $out = "\n     <script type=\"text/javascript\">
        var availableDates = [\"" . implode("\",\"", array_keys($avail_dives)) . "\"];
        var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
        jQuery(document).ready(function($) {
            datepicker_ready('{$D['date']}');
        });
        </script>";
        $out .= "Date: <input type=\"text\" id=\"datepicker\" value=\"{$D['date']}\">";
    } else if ($D['date_selector'] == 'datelist') {
        $avail_dives = uncd_divelog_enumerate_dives($D['data_format']);
        $out = "<select id=\"divepicker\" onchange=\"divelist_change()\">\n";
        foreach ($avail_dives as $dive_date => $dives) {
            foreach ($dives as $dive_id => $dive_time) {
                $out .= "<option value=\"$dive_id\">$dive_id: $dive_date $dive_time</option>\n";
            }
        }
        $out .="</select>\n";
    }
    return uncd_display_final($out);
}

function uncd_divelog_display_init($atts) {
    global $UNC_DIVELOG;
    $a = shortcode_atts( array(
        'options' => false, // we cannot set it to an array here
        'date' => false, // time of the day when we start displaying this date
        'dive_id' => false, // time of the day when we stop displaying this date
        'data_set' => false, // description for the whole day
        'offset' => false,
        'data_format' => 'D4i',
        'start_time' => false, // when the dive actually started, if the internal time is wrong
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
    if (!$a['dive_id']) {
        $UNC_DIVELOG['display']['dive_id'] = 83;
    } else {
        $UNC_DIVELOG['display']['dive_id'] = $a['dive_id'];
    }
    
    // we set the fixed start time if available
    $UNC_DIVELOG['display']['start_time'] = trim($a['start_time']);
    
    // data format of that dive
    $UNC_DIVELOG['display']['data_format'] = $a['data_format'];

    $UNC_DIVELOG['display']['date_selector'] = false;
    if (in_array('calendar', $options)) {
        $UNC_DIVELOG['display']['date_selector'] = 'calendar';
    } else if (in_array('datelist', $options)) {
        $UNC_DIVELOG['display']['date_selector'] = 'datelist';
    }
    $UNC_DIVELOG['display']['date'] = uncd_divelog_dive_latest();
}


function uncd_display_final($out) {
    global $UNC_DIVELOG;
    $D = $UNC_DIVELOG['display'];
    $data = uncd_divelog_query();
    $chart_data = $data['dive_path'];
    if ($D['start_time']) {
        $start_time = $D['start_time'];
    } else {
        $start_time = $data['start_time'];
    }
    
    $file_list = uncd_gallery_data($start_time, $data['dive_time']);
    
    echo $start_time; // TODO: adjust time
    $date_obj = new DateTime($start_time);
    $last_file_match_time = $start_time;
    $final_data = array();
    foreach ($chart_data as $dive_point) {
        if ($dive_point['time'] > 0 ) {
            $date_obj->add(new DateInterval("PT" . $dive_point['time'] . "S"));
        }
        $time = $date_obj->format("H:i:s");
        $count = '';
        $file_match_time = $date_obj->format("Y-m-d H:i:s");
        foreach ($file_list as $file_time => $file_data) {
            if ($file_time < $file_match_time && $file_time >= $last_file_match_time) {
                $count .= '<img src=\'' . $file_data['thumb_url'] . "'>";
            }
        }
        unset($file_list[$file_match_time]);
        
        $depth = $dive_point['depth'] * -1;
        $final_data[$time] = array(
            'depth' => array(
                'value' => $depth,
                'text' => "{$depth}m",
            ),
            'temperature' => array(
                'value' => $dive_point['temp'],
                'text' => "{$dive_point['temp']} C",            
            )
        );
        if ($count != '') {
            $final_data[$time]['depth']['text'] .= $count;
            $final_data[$time]['depth']['bullet'] = "'" . plugin_dir_url( __FILE__ ) . "css/images/camera.png'";
        }
        $last_file_match_time = $file_match_time;
    }
    $out .= '<div class="dives">';
    $out .= uncd_divelog_javachart($final_data, 'Time', 'none', array('temperature' => 'right', 'depth' => 'left'), 'amchart', false, 500);
    $out .= "</div>";
    return $out;
}

function uncd_display_gallery_link(){


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
function uncd_divelog_javachart($data, $y_axis_name, $stacktype, $axis_groups = false, $name = 'amchart', $sum = true, $height = 500) {
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
        foreach ($line as $type => $L) { 
            $nice_text = ucwords($type);
            $graphs[$type] = $nice_text;
            $out .= " \"$type\": {$L['value']}, \"{$nice_text}_text\": \"{$L['text']}\",";
            if (isset($L['bullet'])) {
                $out .= " \"{$nice_text}_bullet\": {$L['bullet']},";
            }
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
                $graphaxis = ',"valueAxis": "'.$graph.'"';
            }
        }
        // 
        $out .= "{
            \"title\": \"$title\",
            \"valueField\": \"$graph\",
            \"fillAlphas\": 0.6,
            \"balloonText\": \"[[{$title}_text]]\",
            \"customBulletField\": \"{$title}_bullet\",
            \"bulletSize\": 14
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