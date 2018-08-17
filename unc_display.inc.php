<?php

function uncd_display_ajax_datepicker() {
    $dive_id = filter_input(INPUT_GET, 'dive_id', FILTER_SANITIZE_NUMBER_INT);
    $source = filter_input(INPUT_GET, 'source_name', FILTER_SANITIZE_STRING);
    ob_clean();
    echo uncd_display_shortcode(array('dive_id' => $dive_id, 'echo' => true, 'source' => $source . ".db"));;
    wp_die();    
}

function uncd_display_shortcode($atts = array()) {
    global $UNC_DIVELOG, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    
    uncd_divelog_display_init($atts);
    
    if (!$UNC_DIVELOG['library_active']) {
        return "Please provide the correct path to the unc_dive library in the admin config!";
    }

    $D = $UNC_DIVELOG['display'];
    $source = $D['source_name'];

    $out = '';
    
    
    if ($D['date_selector'] == 'calendar') {
        $avail_dives = $UNC_DIVELOG['dive_object']->dive_list();
        $out = "\n     <script>
        var availableDates = [\"" . implode("\",\"", array_keys($avail_dives)) . "\"];
        var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
        jQuery(document).ready(function($) {
            datepicker_ready('{$D['date']}');
        });
        </script>";
        $out .= "Date: <input type=\"text\" id=\"datepicker\" value=\"{$D['date']}\">";
    } else if ($D['date_selector'] == 'datelist') {
        $avail_dives = $UNC_DIVELOG['dive_object']->dive_list();
        $chart_id = $UNC_DIVELOG['chart_id'];
        $out = "<select id=\"divepicker\" onchange=\"divelist_change(this.value, '$source', '$chart_id')\">\n";
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
    global $UNC_DIVELOG, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    
    $a = shortcode_atts( array(
        'options' => false, // we cannot set it to an array here
        'date' => false, // show a specific date
        'dive_id' => false, // which dive do we show?
        'data_set' => false, // description for the whole day
        'offset' => false,
        'data_format' => 'Suunto_D4i', // we only recognize this so far. We either need a detection or this needs to be set
        'start_time' => false, // when the dive actually started, if the internal time is wrong
        'date_selector' => false,
        'source' => 'default.db', // this is the default filename without extension
    ), $atts);
    
    // we create a unique chart ID so that AJAX can update the right chart in case there are several on one page
    // such as on the admin screen
    $UNC_DIVELOG['chart_id'] = time();;

    // there can be several options, separated by space
    if (!$a['options']) {
        $options = array();
    } else {
        $options = explode(" ", $a['options']);
    }
    $UNC_DIVELOG['options'] = $options;

    $UNC_DIVELOG['display']['source'] = $a['source'];
    $UNC_DIVELOG['display']['source_name'] = pathinfo($a['source'], PATHINFO_FILENAME);
    $UNC_DIVELOG['display']['data_format'] = $a['data_format'];
    $UNC_DIVELOG['display']['source_path'] = $UNC_DIVELOG['datapath']. DIRECTORY_SEPARATOR . $a['source'];

    // initialize dive data
    $UNC_DIVELOG['dive_object'] = new unc_dive();
    $UNC_DIVELOG['dive_object']->set_computer_type($a['data_format']);
    $UNC_DIVELOG['dive_object']->set_source($UNC_DIVELOG['display']['source_path']);
    
    // which dive ID, default to latest
    if (!$a['dive_id']) {
        $latest_dive = $UNC_DIVELOG['dive_object']->get_latest_dive_id();
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("laste dive", $latest_dive);}
        $UNC_DIVELOG['display']['dive_id'] = $latest_dive;
    } else {
        $UNC_DIVELOG['display']['dive_id'] = $a['dive_id'];
    }

    // we set the fixed start time if available
    $UNC_DIVELOG['display']['start_time'] = trim($a['start_time']);
    $UNC_DIVELOG['display']['offset'] = trim($a['offset']);

    // data format of that dive
    $UNC_DIVELOG['display']['data_format'] = $a['data_format'];

    $UNC_DIVELOG['display']['date_selector'] = false;
    if (in_array('calendar', $options)) {
        $UNC_DIVELOG['display']['date_selector'] = 'calendar';
    } else if (in_array('datelist', $options)) {
        $UNC_DIVELOG['display']['date_selector'] = 'datelist';
    }
}

/**
 * display the actual data
 * 
 * @global type $UNC_DIVELOG
 * @param type $out
 * @return string
 */
function uncd_display_final($out) {
    global $UNC_DIVELOG, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $D = $UNC_DIVELOG['display'];
    $data = $UNC_DIVELOG['dive_object']->dive_get_one($D['dive_id']);
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("dive data", $data);}
    $chart_data = $data['dive_path'];

    // link the chart name to the source file
    $chart_id = 'amchart' . $UNC_DIVELOG['chart_id'];    
    
    if ($D['start_time']) {
        $start_time = $D['start_time'];
    } else if ($D['offset']) {
        $start_time = date("Y-m-d H:i:s", strtotime($D['offset'], strtotime($data['start_time'])));
    } else {
        $start_time = $data['start_time'];
    }
    
    if (is_null($data['dive_time'])) {
        return "<!-- DEBUG: " . var_export($D, true) . " -->";
    }
    
    // get the gallery data
    $file_list = uncd_gallery_data($start_time, $data['dive_time']);
    //echo $start_time;
    if ($UNC_DIVELOG['user_settings']['chart_time_option'] == 'time') {
        $date_obj = new DateTime($start_time);
    } else {
        $date_obj = new DateTime('2000-01-01 00:00:00');
    }

    $curr_secs = 0;
    $last_dive_point = 0;
    $final_data = array();
    foreach ($chart_data as $i => $dive_point) {
        $depth = $dive_point['depth'] * -1; // we dive deep, not high

        if ($dive_point['time'] > 0 ) {
            $date_obj->add(new DateInterval("PT" . $dive_point['time'] . "S"));
        }
        $curr_secs += $dive_point['time'];
        $time = $date_obj->format("H:i:s");
        // first we define the dive points. If an image exists at the same time
        // this data will be replaced.
        $final_data[$time] = array(
            'depth' => array(
                'value' => $depth,
                'text' => "{$depth}m",
            ),
            'temperature' => array(
                'value' => $dive_point['temp'],
                'text' => "{$dive_point['temp']}&deg;C",
            )
        );
        foreach ($file_list as $file_time => $file_data) {
            if ($last_dive_point <= $file_time && $file_time <= $curr_secs) {
                // next depth:
                $next_depth = $chart_data[$i + 1]['depth'] * -1;
                $depth_per_sec = abs($depth - $next_depth) / $dive_point['time'];
                $time_gap = $file_time - $curr_secs;
                $pic_depth = $depth_per_sec * $time_gap + $depth;
                $text = $pic_depth . 'm<br><img src=\'' . $file_data['thumb_url'] . "'>";
                $temp_date = $date_obj;
                $temp_date->sub(new DateInterval("PT" . abs($time_gap) . "S"));
                $photo_time = $temp_date->format("H:i:s");
                $final_data[$photo_time] = array(
                    'depth' => array(
                        'value' => $pic_depth,
                        'text' => $text,
                        'bullet' => "'" . plugin_dir_url( __FILE__ ) . "css/images/camera.png'",
                    ),
                    'temperature' => array(
                        'value' => $dive_point['temp'],
                        'text' => "{$dive_point['temp']}&deg;C",
                    )
                );
                unset($file_list[$file_time]);
            }
        }
        $last_dive_point = $curr_secs;
    }
    $out .= $UNC_DIVELOG['headline'];

    $axis_data = array(
        'temperature' => array('position' => 'right', 'lineColor' => "#b7e021", 'fillAlphas' => 0),
        'depth' => array('position' => 'left', 'lineColor' => "#2498d2", 'fillAlphas' => 0.6),
    );



    $out .= uncd_divelog_javachart($final_data, 'Time', 'none', $axis_data, $chart_id, false);
    // TODO: Make this one an option
    $out .= "<small>Dive computer start time: " . $data['start_time'] . "</small><br>";
    return $out;
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
function uncd_divelog_javachart($data, $y_axis_name, $stacktype, $axis_groups = false, $name = 'amchart', $sum = true, $height = 400) {
    global $UNC_DIVELOG, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // check the stack type
    $valid_stacktypes = array("none", "regular", "100%", "3d");
    if (!in_array($stacktype, $valid_stacktypes)) {
        return 'Invalid stacktype!';
    }
       
    $out = "\n<div style=\"width: 100%; height: {$height}px; font-size: 11px;\" id=\"$name\"></div>\n";
    $out .= "<script>
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
        "guides": [{
            "dashLength": 9,
            "above": true,
            "inside": true,
            "label": "5m",
            "lineAlpha": 0.8,
            "value": -5.0,
            "valueAxis": "depth",
            "lineColor": "#009933"
        },{
            "dashLength": 9,
            "above": true,
            "inside": true,
            "label": "30m",
            "lineAlpha": 0.8,
            "value": -30.0,
            "valueAxis": "depth",
            "lineColor": "#cc0000"
        }        ],
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
            if (isset($axis_groups[$graph]['position'])) {
                $valaxis .= '{"id": "'.$graph.'", "title": "'.$title.'", "position": "'.$axis_groups[$graph]['position'].'"},';
                $graphaxis = ',"valueAxis": "'.$graph.'"';
            }
        }
        //
        $out .= "{
            \"title\": \"$title\",
            \"valueField\": \"$graph\",
            \"fillAlphas\": {$axis_groups[$graph]['fillAlphas']},
            \"balloonText\": \"[[{$title}_text]]\",
            \"customBulletField\": \"{$title}_bullet\",
            \"lineColor\": \"{$axis_groups[$graph]['lineColor']}\",
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

/**
 * Get the photos for the dive from the gallery plugin
 * 
 * @global type $UNC_GALLERY
 * @param type $start_time
 * @param type $dive_time
 * @return type
 */
function uncd_gallery_data($start_time, $dive_time) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $start_obj = DateTime::createFromFormat("Y-m-d H:i:s", $start_time);
    $int = new DateInterval('PT' . $dive_time . 'S');
    $start_obj->add($int);
    $end_time = $start_obj->format("Y-m-d H:i:s");

    $args = array(
        'start_time' => $start_time, // UNIX timestamp
        'end_time' => $end_time,  // UNIX timestamp
        'featured' => false,
        'description' => false,
    );

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__ . " dislplay_var_init args", $args);}
    unc_gallery_display_var_init($args);

    $files = $UNC_GALLERY['display']['files'];

    $file_list = array();
    foreach ($files as $file) {
        $time = $file['file_date'];
        // for now, we just aggregate the images by minute so we do not show more than one image per minute
        $sec_from_start = strtotime($time) - strtotime($start_time);
        $file_list[$sec_from_start] = $file;
    }

    return $file_list;
}