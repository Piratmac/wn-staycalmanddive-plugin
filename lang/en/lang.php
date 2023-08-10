<?php return [
    'plugin' => [
        'name' => 'StayCalmAndDive',
        'description' => 'A dive calculator

USE AT YOUR OWN RISK'
    ],
    'form' => [
        'parameters' =>                       'Parameters',
        'multiple_dives' =>                   'Multiple dives',
        'advanced_parameters' =>              'Advanced parameters. Use at your own risk!',
        'use_nitrox' =>                       'Use Nitrox',
        'use_oxygen' =>                       'Use Oxygen',
        'max_oxygen_pressure' =>              'Max oxygen pressure (in bars)',
        'atmospheric_pressure' =>             'Atmospheric pressure (in bars)',
        // Dive parameters
        'start_time' =>                       'Start time',
        'dive_duration' =>                    'Bottom time',
        'dive_duration_notes' =>              'Duration before ascent starts (min)',
        'max_depth' =>                        'Max depth',
        'depth_when_ascent_starts' =>         'Depth when ascent starts',
        'depth_when_ascent_starts_notes' =>   'In case of slow ascent.',
        'oxygen_at_stops' =>                  'Use oxygen for all deco stops',
        'nitrox_ratio' =>                     'Nitrox ratio (% of oxygen)',
        'air_nitrogen_ratio' =>               'Nitrogen ratio in air (in %)',
        // Incidents
        'interrupted_stop' =>                 'Incident: a stop was not completed',
        'interrupted_stop_depth' =>           'Which stop was not completed?',
        'interrupted_stop_duration' =>        'The stop was interrupted after (min):',
        'surface_duration' =>                 'How much time did the divers stay on the surface?',
        'quick_ascent' =>                     'Incident: the ascent was too quick',
        'quick_ascent_when' =>                'The incident happened after: (min)',
        'quick_ascent_duration' =>            'How much time did the ascent last?',
        // Interval parameters
        'use_interval_duration' =>            'Enter interval duration instead of start of next dive?',
        'interval_duration' =>                'Interval duration (minutes)',
        'use_oxygen_at_surface' =>            'Use oxygen during interval?',
        'interval_oxygen_when' =>             'When is the oxygen breathed?',
        'interval_oxygen_breathing_start' =>  'At the start',
        'interval_oxygen_breathing_end' =>    'At the end',
        'interval_oxygen_duration' =>         'Oxygen breathing duration (minutes)',
        // Submit!
        'calculate' =>                        'Calculate',
    ],

// Calculation errors
    'calculation' => [
        'errors' => [
            'both_incidents' =>            'This model doesn\'t cover both incidents happening at the same time.',
            'dives_overlap' =>             'Dive 2 starts before the end of dive 1.',

            'nitrox_dangerous' =>          'At this depth, breathing this mix is not safe.',
            'too_deep_for_tables' =>       'MN90 tables do not cover this case (too deep)',
            'too_long_for_tables' =>       'MN90 tables do not cover this case (too lengthy for this depth)',
            'GPS_is_star' =>               'GPS is *, successive dives are forbidden.',
            'residual_nitrogen_too_high' =>'MN90 tables do not cover this level of residual nitrogen.',
        ],
        'warnings' => [
            'no_stop_at_that_depth' =>'Interrupted stop: There is no stop planned at that depth. The incident will be ignored.',
            'max_depth_above_depth_when_ascent_starts' =>       'Max depth is higher than the depth at end of dive. Max depth was adjusted for the calculation.',
            'stop_too_short' =>       'Interrupted stop: According to the calculation, the stop was interrupted after its end... The incident will be ignored.',
            'dive_too_short' =>       'Quick ascent: the dive didn\'t last that long. The incident will be ignored.',
        ]
    ],

    // Output of dives
    'dive' => [
        'first_dive' =>                       'First dive',
        'second_dive' =>                      'Second dive',
        'max_depth' =>                        'Max depth',
        'nitrox_equivalent_depth' =>          'Nitrox - Equivalent of max depth',
        'nitrox_max_operating_depth' =>       'Nitrox - Maximum operating depth',
        'max_depth_table' =>                  'Depth in table',
        'duration_calculation' =>             'Duration calculation',
        'depth_calculation' =>                'Depth calculation',
        'start_time' =>                       'Start of dive',
        'duration' =>                         'Duration',
        'duration_for_calculation' =>         'Duration for calculation',
        'duration_with_majoration' =>         'Duration with majoration',
        'duration_in_table' =>                'Duration in table',
        'stops' =>                            'Stops',
        'stops_calculation' =>                'Stops calculation',
        'before_incident' =>                  'Before incident',
        'after_incident' =>                   'After incident',
        'depth_when_ascent_starts' =>         'Depth when ascent starts',
        'incident_time' =>                    'Incident time',
        'time_when_ascent_starts' =>          'Time when ascent starts',
        'total_ascent_time' =>                'Total ascent time',
        'stop_depth' =>                       '%depth% m stop',
        'surface_time' =>                     'Surface time',
        'GPS_at_end' =>                       'GPS at end of dive',
    ],

    // Output of interval
    'interval' => [
        'interval' =>                         'Interval',
        'consecutive_dives' =>                'Consecutive dives. No interval data.',
        'first_gas' =>                        'First gas',
        'second_gas' =>                       'Second gas',
        'nitrogen_elimination' =>             'Nitrogen elimination',
        'gas_breathed' =>                     'Gas breathed',
        'GPS_at_start' =>                     'GPS at start of interval',
        'GPS_after_first_gas' =>              'GPS after first gas',
        'duration' =>                         'Duration',
        'duration_in_table' =>                'Duration in table',
        'residual_nitrogen' =>                'Residual nitrogen',
        'residual_nitrogen_in_table' =>       'Residual nitrogen in table',
        'depth_in_table' =>                   'Depth in table',
        'majoration_calculation' =>           'Majoration calculation',
        'majoration' =>                       'Majoration',
        'gas_breathed_air' =>                 'Air',
        'gas_breathed_oxygen' =>              'Oxygen',
    ],

    // Miscellaneous elements
    'various' => [
        'depth_value' =>                      ':depth m',
        'depth_value_3m' =>                   '3 m',
        'depth_value_6m' =>                   '6 m',
        'depth_value_9m' =>                   '9 m',
        'depth_value_12m' =>                  '12 m',
        'depth_value_15m' =>                  '15 m',
        'time_format' =>                      'H:i',
        'duration_format_minutes' =>          ':minutes min',
        'duration_format_hour_minutes' =>     ':duration min (:hoursh:minutes)',
    ],
    'graph' => [
        'GPS' =>                              'GPS: :GPS',
        'depth' =>                            'Depth',
        'time_format' =>                      'H:mm',
        'total_ascent_time' =>                'Ascent: :duration',
        'majoration' =>                       'Majoration: :majoration min',
        'residual_nitrogen' =>                'Nitrogen: :residual_nitrogen',
    ]
];