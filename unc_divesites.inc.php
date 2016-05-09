<?php

function uncd_divesites_shortcode($atts = array()) {
    // first, get all categories and their data

    global $UNC_DIVELOG;

    if (strlen($UNC_DIVELOG['google_api_key']) < 1) {
        return "You need to set a google api key in the configuration to use this feature!";
    }

    $cats = get_categories();

    $lats = array();
    $lens = array();
    $markers_list = 'var points = [';
    $z_index = 100;

    $levels = array(
        'Countries' => 2,
        'Region' => 9,
        'Divesite'  => 11
    );

    $level = filter_input(INPUT_GET, 'level', FILTER_SANITIZE_STRING);
    $level_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if (!isset($levels[$level])) {
        $level = 'Countries';
    }

    $level_name = '';

    foreach ($cats as $C){
        if ($level_id == $C->cat_ID) {
            $level_name = $C->name;
        }

        if ($level == 'Countries' && $C->parent <> 0) {
            continue;
        } else if ($level == 'Countries') { // link to region map
            $link = get_page_link() . '?level=Region&id=' . $C->cat_ID;
        } else if (($level == 'Region' || $level == 'Divesite') && $C->parent <> $level_id) {
            continue;
        } else if ($level == 'Region') {
            $link = get_page_link() . '?level=Divesite&id=' . $C->cat_ID;
        } else if ($level == 'Divesite') {
            $link = get_category_link($C->cat_ID);
        }
        // var_dump($C);
        $location_name = $C->name . " (" . $C->count . " dives)";

        //echo $C->parent;
        //echo $C->description;
        //echo $C->slug;
        //echo $C->count;

        $coords = explode(",", $C->description);
        if (count($coords) != 2) {
            continue;
        }
        $lat = trim($coords[0]);
        $lats[] = $lat;
        $len = trim($coords[1]);
        $lens[] = $len;

        $markers_list .= "['$location_name',$lat,$len,$z_index,'$link'],\n";
        $z_index ++;
    }
    $markers_list .= "];\n";
    $avg_lat = array_sum($lats) / count($lats);
    $avg_len = array_sum($lens) / count($lens);

    $zoom = $levels[$level];

    $out = "<h2>Detail Level: $level $level_name</h2>";

    $out .= '
    <div id="map" style="height:600px"></div>
    <script>
        var map;
        var marker;
        function initMap() {
            map = new google.maps.Map(document.getElementById(\'map\'), {
                center: {lat: '.$avg_lat.', lng: '.$avg_len.'},
                zoom: '.$zoom.'
            });
            ' . $markers_list . '
            for (var i = 0; i < points.length; i++) {
                var point = points[i];
                marker = new google.maps.Marker({
                    position: {lat: point[1], lng: point[2]},
                    map: map,
                    title: point[0],
                    zIndex: point[3],
                    url: point[4]
                });
                google.maps.event.addListener(marker, \'click\', function() {
                    window.location.href = this.url;
                });
            }
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key='.$api_key.'&callback=initMap" async defer></script>';
    return $out;
}


// empty ountries:
// [ { "featureType": "landscape.natural.landcover", "elementType": "geometry.fill", "stylers": [ { "visibility": "off" } ] } ]