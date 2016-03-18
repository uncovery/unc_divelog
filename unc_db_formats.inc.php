<?php

$UNC_DIVELOG['data_structure'] = array(
    'D4i' => array(
        'fieldmap' => array(
            'dive_number' => array('field_name' => 'DiveId'),
            'start_time' => array('field_name' => 'StartTime', 'format' => 'seconds_since_0001'),
            'max_depth' => array('field_name' => 'MaxDepth'),
            'avg_depth' => array('field_name' => 'AvgDepth'),
            'serial_no' => array('field_name' => 'SerialNumber'),
            'dive_path' => array('field_name' => 'quote(SampleBlob)', 'format' => 'D4i_SampleBlob'),
        ),
        'sample_data' => array(
            'DiveId' => 83,
            'StartTime' => '635932062070000000',
            'Duration' => 3105,
            'Mode' => 1,
            'SourceSerialNumber' => NULL,
            'Source' => 'D4i',
            'MaxDepth' => 27.329999999999998,
            'SampleInterval' => 20,
            'Note' => '',
            'StartTemperature' => 31,
            'BottomTemperature' => 29,
            'EndTemperature' => 29,
            'StartPressure' => NULL,
            'EndPressure' => NULL,
            'AltitudeMode' => 0,
            'PersonalMode' => 0,
            'CylinderVolume' => 12,
            'CylinderWorkPressure' => 232000,
            'ProfileBlob' => 'Blob',
            'TemperatureBlob' => 'Blob',
            'PressureBlob' => 'Blob',
            'DiveNumberInSerie' => 2,
            'TissuePressuresNitrogenStartBlob' => 'Blob',
            'TissuePressuresHeliumStartBlob' => 'Blob',
            'SurfaceTime' => 5280,
            'CnsStart' => 8,
            'OtuStart' => 32,
            'OlfEnd' => 26,
            'DeltaPressure' => NULL,
            'DivingDaysInRow' => NULL,
            'SurfacePressure' => 101800,
            'PreviousMaxDepth' => NULL,
            'DiveTime' => NULL,
            'Deleted' => NULL,
            'Weight' => 0,
            'Weather' => 0,
            'Visibility' => 0,
            'DivePlanId' => NULL,
            'SetPoint' => NULL,
            'AscentTime' => NULL,
            'BottomTime' => NULL,
            'CnsEnd' => 23,
            'OtuEnd' => 77,
            'TissuePressuresNitrogenEndBlob' => 'Blob',
            'TissuePressuresHeliumEndBlob' => 'Blob',
            'Boat' => NULL,
            'SampleBlob' => 'Blob',
            'AvgDepth' => 17.379999999999999,
            'Algorithm' => 1,
            'LowSetPoint' => NULL,
            'LowSwitchPoint' => NULL,
            'HighSwitchPoint' => NULL,
            'MinGf' => NULL,
            'MaxGf' => NULL,
            'Partner' => NULL,
            'Master' => NULL,
            'DesaturationTime' => NULL,
            'Software' => '1.2.10',
            'SerialNumber' => '33200647',
            'TimeFromReset' => NULL,
            'Battery' => 3.0999999046325684,
        ),
    )
);
