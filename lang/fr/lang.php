<?php return [
    'plugin' => [
        'name' => 'StayCalmAndDive',
        'description' => 'Un calculateur de plongée

USAGE A VOS RISQUES ET PERILS'
    ],
    'form' => [
        'parameters' =>                       'Paramètres',
        'multiple_dives' =>                   'Plongées multiples',
        'advanced_parameters' =>              'Paramètres avancées. A vos risques et périls!',
        'use_nitrox' =>                       'Utilisation de Nitrox',
        'use_oxygen' =>                       'Utilisation d\'oxygène',
        'max_oxygen_pressure' =>              'Pression maxi pour l\'oxygène (en bars)',
        'atmospheric_pressure' =>             'Pression atmosphérique (en bars)',
        // Dive parameters
        'start_time' =>                       'Heure de début',
        'dive_duration' =>                    'Durée de la plongée',
        'dive_duration_notes' =>              'Durée avant début de la remontée (min)',
        'max_depth' =>                        'Profondeur maximale',
        'depth_when_ascent_starts' =>         'Profondeur au début de la remontée',
        'depth_when_ascent_starts_notes' =>   'En cas de remontée lente',
        'oxygen_at_stops' =>                  'Utilisation d\'oxygène aux paliers',
        'nitrox_ratio' =>                     'Ratio de Nitrox (% d\'oxygène)',
        'air_nitrogen_ratio' =>               'Ratio d\'oxygène dans l\'air (% d\'azote)',
        // Incidents
        'interrupted_stop' =>              'Incident: palier interrompu',
        'interrupted_stop_depth' =>        'Quel palier a été interrompu?',
        'interrupted_stop_duration' =>     'La palier a été interrompu après (min):',
        'surface_duration' =>              'Durée passée en surface',
        'quick_ascent' =>                  'Incident: remontée rapide',
        'quick_ascent_when' =>             'La remontée a commencé au bout de : (min)',
        'quick_ascent_duration' =>         'Durée de la remontée rapide',

        // Interval parameters
        'use_interval_duration' =>            'Indiquer la durée de l\'intervalle',
        'interval_duration' =>                'Durée de l\'intervalle (min)',
        'use_oxygen_at_surface' =>            'Utilisation d\'oxygène pendant l\'intervalle',
        'interval_oxygen_when' =>             'Quand l\'oxygène est-il respiré?',
        'interval_oxygen_breathing_start' =>  'Au début',
        'interval_oxygen_breathing_end' =>    'A la fin',
        'interval_oxygen_duration' =>         'Durée de respiration d\'oxygène (minutes)',
        // Submit!
        'calculate' =>                        'Calculer',
    ],

// Calculation errors
    'calculation' => [
        'errors' => [
            'both_incidents' =>            'Ce modèle ne couvre pas le cas d\'incidents multiples.',
            'dives_overlap' =>             'La plongée 2 commence avant la fin de la plongée 1.',

            'nitrox_dangerous' =>          'A cette profondeur, ce mélange est dangereux.',
            'too_deep_for_tables' =>       'Les tables MN90 ne couvrent pas ce cas (trop profond).',
            'too_long_for_tables' =>       'Les tables MN90 ne couvrent pas ce cas (trop long pour cette profondeur).',
            'GPS_is_star' =>               'Le GPS est *, les plongée successives sont interdites.',
            'residual_nitrogen_too_high' =>'Les tables MN90 ne couvrent pas ce cas (trop d\'azote résiduel).',
        ],
        'warnings' => [
            'no_stop_at_that_depth' =>'Palier interrompu: Aucun palier prévu pour cette profondeur. Incident ignoré.',
            'max_depth_above_depth_when_ascent_starts' =>       'La profondeur maxi est plus haute que celle enfin de plongée. La profondeur max a été ajustée.',
            'stop_too_short' =>       'Palier interrompu: Palier interromptu après sa fin. Incident ignoré.',
            'dive_too_short' =>       'Remontée rapide: La plongée était déjà terminée. Incident ignoré.',
        ]
    ],

    // Output of dives
    'dive' => [
        'first_dive' =>                           'Première plongée',
        'second_dive' =>                          'Seconde plongée',
        'max_depth' =>                            'Profondeur maximale',
        'nitrox_equivalent_depth' =>              'Nitrox - Profondeur air équivalente',
        'nitrox_max_operating_depth' =>           'Nitrox - Profondeur maximale autorisée (MOD)',
        'max_depth_table' =>                      'Profondeur dans les tables',
        'duration_calculation' =>                 'Calcul de la durée',
        'depth_calculation' =>                    'Calcul de la profondeur',
        'start_time' =>                           'Début de la plongée',
        'duration' =>                             'Durée',
        'duration_for_calculation' =>             'Durée pour le calcul',
        'duration_with_majoration' =>             'Durée avec majoration',
        'duration_in_table' =>                    'Durée dans les tables',
        'stops' =>                                'Paliers',
        'stops_calculation' =>                    'Calcul des paliers',
        'before_incident' =>                      'Avant incident',
        'after_incident' =>                       'Après incident',
        'depth_when_ascent_starts' =>             'Profondeur en début de remontée',
        'incident_time' =>                        'Heure de l\'incident',
        'time_when_ascent_starts' =>              'Heure de début de remontée',
        'total_ascent_time' =>                    'Durée totale de remontée (DTR)',
        'stop_depth' =>                           'Palier à %depth% m',
        'surface_time' =>                         'Heure de sortie',
        'GPS_at_end' =>                           'GPS en fin de plongée',
    ],

    // Output of interval
    'interval' => [
        'interval' =>                         'Intervalle de surface',
        'consecutive_dives' =>                'Plongée consécutives. Pas d\'intervalle.',
        'first_gas' =>                        '1er gaz',
        'second_gas' =>                       '2e gaz',
        'nitrogen_elimination' =>             'Elimination de l\'azote',
        'gas_breathed' =>                     'Gaz respiré',
        'GPS_at_start' =>                     'GPS au début de l\'intervalle',
        'GPS_after_first_gas' =>              'GPS après le 1er gaz',
        'duration' =>                         'Durée',
        'duration_in_table' =>                'Durée dans les tables',
        'residual_nitrogen' =>                'Azote résiduel',
        'residual_nitrogen_in_table' =>       'Azote résiduel dans les tables',
        'depth_in_table' =>                   'Profondeur dans les tables',
        'majoration' =>                       'Majoration',
        'majoration_calculation' =>           'Calcul de la majoration',
        'gas_breathed_air' =>                 'Air',
        'gas_breathed_oxygen' =>              'Oxygène',
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
        'duration_format_hour_minutes' =>     '(:hours h:minutes)',
    ],
    'graph' => [
        'GPS' =>                              'GPS: :GPS',
        'depth' =>                            'Profondeur',
        'time_format' =>                      'H:mm',
        'total_ascent_time' =>                'DTR: :duration',
        'majoration' =>                       'Majoration: :majoration min',
        'residual_nitrogen' =>                'Azote: :residual_nitrogen',
    ]
];