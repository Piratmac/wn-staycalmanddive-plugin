<?php namespace Piratmac\Staycalmanddive\Models;

use Model;
use Winter\Storm\Exception\ValidationException as ValidationException;
use Piratmac\Staycalmanddive\Classes\MN90Tables as MN90Tables;

/**
 * Entry Model
 */
class Dive extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    //public $table = 'piratmac_staycalmanddive_dives';

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [
        'start_time'  => 'date|required',
        'duration'  => 'integer|between:1,360|required',
        'max_depth'  => 'integer|between:1,65|required',
        'depth_when_ascent_starts'  => 'integer|between:1,65',
        'nitrox_ratio' => 'integer|between:1,99|required_if:use_nitrox,1',
        'interrupted_stop_depth' => 'in:3,6,9,12,15|required_if:interrupted_stop,1',
        'interrupted_stop_duration' => 'integer|between:1,99|required_if:interrupted_stop,1',
        'interrupted_stop_surface_time' => 'integer|between:1,360|required_if:interrupted_stop,1',
        'quick_ascent_when' => 'integer|between:1,360|required_if:quick_ascent,1',
        'quick_ascent_duration' => 'integer|between:1,360|required_if:quick_ascent,1',
        'quick_ascent_surface_time' => 'integer|between:1,360|required_if:quick_ascent,1',
        'atmospheric_pressure' => 'numeric|between:0.1,1',
        'air_nitrogen_ratio' => 'numeric|between:75,85',
    ];

    protected $casts = [
        'duration'  => 'integer',
        'max_depth'  => 'integer',
        'depth_when_ascent_starts'  => 'integer',
        'nitrox_ratio' => 'integer',
        'use_oxygen' => 'boolean',
        'interrupted_stop' => 'boolean',
        'interrupted_stop_depth' => 'integer',
        'interrupted_stop_duration' => 'integer',
        'interrupted_stop_surface_time' => 'integer',
        'quick_ascent' => 'boolean',
        'quick_ascent_when' => 'integer',
        'quick_ascent_duration' => 'integer',
        'quick_ascent_surface_time' => 'integer',
        'atmospheric_pressure' => 'float',
        'air_nitrogen_ratio' => 'integer',

        'residual_nitrogen' => 'float',
    ];

    protected $dates = ['start_time', 'end_time'];

    public $fillable = [
        'start_time','duration','max_depth','depth_when_ascent_starts',
        'interrupted_stop','interrupted_stop_depth','interrupted_stop_duration','interrupted_stop_surface_time',
        'nitrox_ratio','air_nitrogen_ratio','use_oxygen',
        'quick_ascent','quick_ascent_duration','quick_ascent_surface_time','quick_ascent_when',
        'atmospheric_pressure',

        'residual_nitrogen'
    ];

    public $garded = ['majoration'];


    /**
     * @var array Relations
     */
    public $hasOne = [
        'divelog' => ['Piratmac\Staycalmanddive\Models\DiveLog'],
    ];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public $calculationWarnings = [];
    public $deco_stops = [];
    public $dots = [];
    public $time_to_first_stop = 0;
    public $incident_type = '';
    public $residual_nitrogen = 0.79;
    public $data_table = [];


    public function afterValidate() {
        // Both incidents ==> can't calculate
        if ($this->quick_ascent && $this->interrupted_stop)
            throw new ValidationException(['depth_when_ascent_starts' => trans('piratmac.staycalmanddive::lang.calculation.errors.both_incidents')]);

        // Ascent starts below max depth ==> adjust max depth
        if ($this->depth_when_ascent_starts > $this->max_depth) {
            $this->calculationWarnings[] = trans('piratmac.staycalmanddive::lang.calculation.warnings.max_depth_above_depth_when_ascent_starts');
            $this->max_depth = $this->depth_when_ascent_starts;
        }
    }

    public function beforeCalculate($diveLog) {
        // Quick ascent => reduce duration
        if ($this->quick_ascent)
            $this->duration = min($this->duration, $this->quick_ascent_when);

        // Set ascend depth (if unset)
        if (!$this->depth_when_ascent_starts)
            $this->depth_when_ascent_starts = $this->max_depth;

        // Add majoration
        $this->duration_for_calculation = $this->duration + $this->majoration;

        // Store data from divelog for future use
        $this->max_oxygen_pressure = $diveLog->max_oxygen_pressure;

        if (!$this->air_nitrogen_ratio)
            $this->air_nitrogen_ratio = 79;
    }

    public function calculateDecompression() {
        // Calculate and check max depth for Nitrox
        $this->calculateMaxNitroxOperatingDepth();

        $this->MN90Tables = new MN90Tables();

        // Calculate stops
        $this->calculateStops();

        // Calculate ascent profile, end time, ...
        $this->calculateAscentProfile();

        // Apply interrupted stops
        $this->calculateInterruptedStopImpact();

        // Apply impact of a quick ascent
        $this->calculateQuickAscentImpact();
    }

  /*
   * Calculates the maximum Nitrox depth based on the max oxygen pressure used.
   * Raises a ValidationException if the nitrox mix is not safe for that dive
   */
    public function calculateMaxNitroxOperatingDepth() {
        $this->nitrox_maximum_operating_depth = 0;
        if ($this->gas_at_depth['type'] != 'nitrox')
            return;
        $this->nitrox_maximum_operating_depth = ceil((10 * ($this->max_oxygen_pressure / $this->gas_at_depth['O2_ratio'] * 100 - 1))*10)/10;
        if ($this->max_depth > $this->nitrox_maximum_operating_depth)
            throw new ValidationException (['nitrox_ratio' => trans('piratmac.staycalmanddive::lang.calculation.errors.nitrox_dangerous')]);
    }

  /*
   * Calculates the decompression stops
   */
    public function calculateStops() {
        $this->MN90Tables->getStopsForDive($this->max_depth, $this->duration_for_calculation, $this->atmospheric_pressure, $this->air_nitrogen_ratio, $this->gas_at_depth, $this->gas_at_stops);

        $this->equivalent_air_max_depth = ceil(((($this->max_depth + 10) * (100 - $this->gas_at_depth['O2_ratio']) / $this->air_nitrogen_ratio - 10) / $this->atmospheric_pressure *10)/10);
        $this->max_depth_in_table =       $this->MN90Tables->depth;
        $this->duration_in_table =        $this->MN90Tables->duration;
        $this->deco_stops =               array_filter($this->MN90Tables->stops, function($v) { return $v > 0; });
        $this->GPS =                      $this->MN90Tables->GPS;
    }

  /*
   * Calculates the ascent profile (duration, start and end times)
   */
    public function calculateAscentProfile() {
        $this->time_when_ascent_starts = (clone $this->start_time)->addMinutes($this->duration);

        // No decompression
        if ($this->deco_stops == []) {
            $this->total_ascent_time = ceil($this->depth_when_ascent_starts / 15 / $this->atmospheric_pressure*2)/2;
        }
        // With decompression
        else {
            $this->time_to_first_stop = ceil(($this->depth_when_ascent_starts - max(array_keys($this->deco_stops))) / 15 / $this->atmospheric_pressure*2)/2;
            $this->total_ascent_time = $this->time_to_first_stop;
            $this->total_ascent_time += array_sum($this->deco_stops);
            $this->total_ascent_time += 0.5 * count($this->deco_stops);
        }
        // For interrupted stops, remove the last 30 seconds (vertical ascent)
        if ($this->interrupted_stop && !$this->incident_type) {
            $this->total_ascent_time -= 0.5;
        }

        $this->end_time = (clone $this->time_when_ascent_starts)->addSeconds($this->total_ascent_time*60)->ceilUnit('minute', 1);
    }

  /*
   * Apply impact of interrupted stop
   */
    public function calculateInterruptedStopImpact() {
        if (!$this->interrupted_stop)
            return;

        // Verify input data
        if (!array_key_exists($this->interrupted_stop_depth, $this->deco_stops))
            return $this->calculationWarnings[] = trans('piratmac.staycalmanddive::lang.calculation.warnings.no_stop_at_that_depth');
        if ($this->interrupted_stop_duration >= $this->deco_stops[$this->interrupted_stop_depth])
            return $this->calculationWarnings[] = trans('piratmac.staycalmanddive::lang.calculation.warnings.stop_too_short');


        // Shorten this dive + add a new one after it
        $this->incident_dive = clone $this;

        foreach ($this->deco_stops as $depth => $duration) {
            // Remove all stops above than the one when the incident happened
            if ($depth < $this->interrupted_stop_depth)
                unset($this->deco_stops[$depth]);
            elseif ($depth == $this->interrupted_stop_depth)
                $this->deco_stops[$depth] = min($duration, $this->interrupted_stop_duration);
        }

        // Recalculate ascent on main dive
        $this->calculateAscentProfile();

        // Set up a virtual dive, which only contains stops
        $this->incident_dive->start_time = clone $this->end_time->addMinutes($this->interrupted_stop_surface_time);
        $this->incident_dive->duration = 0;
        $this->incident_dive->max_depth = $this->interrupted_stop_depth;
        $this->incident_dive->depth_when_ascent_starts = $this->interrupted_stop_depth;
        $interrupted_stop_depth = $this->interrupted_stop_depth;
        $this->incident_dive->deco_stops = array_filter($this->incident_dive->deco_stops, function ($key) use($interrupted_stop_depth) {
            return $key <= $interrupted_stop_depth;},  ARRAY_FILTER_USE_KEY);
        $this->incident_dive->incident_type = 'interrupted stop';

        $this->incident_dive->calculateAscentProfile();
    }


  /*
   * Apply impact of a quick ascent
   */
    public function calculateQuickAscentImpact() {
        // Check input data
        if (!$this->quick_ascent)
            return;

        if ($this->quick_ascent_when > $this->duration)
            return $this->calculationWarnings[] = trans('piratmac.staycalmanddive::lang.calculation.warnings.dive_too_short');

        // Copy dive before changes
        $this->incident_dive = clone $this;

        // Shorten dive
        $this->deco_stops = [];
        $this->total_ascent_time = $this->quick_ascent_duration;
        $this->time_when_ascent_starts = (clone $this->start_time)->addMinutes($this->duration);
        $this->end_time = (clone $this->time_when_ascent_starts)->addMinutes($this->total_ascent_time);

        // Set up a virtual dive, which has the 5 minutes + all stops
        $this->incident_dive->start_time = clone $this->end_time->addMinutes($this->quick_ascent_surface_time);
        $this->incident_dive->duration = 5;
        $this->incident_dive->depth_when_ascent_starts = ceil($this->max_depth / 2);
        // The majoration contains the entire duration of the initial dive
        // So now it'll calculate the stops for the full duration
        $this->incident_dive->majoration = $this->majoration + $this->duration + $this->quick_ascent_duration + $this->quick_ascent_surface_time;
        $this->incident_dive->duration_for_calculation = $this->incident_dive->duration + $this->incident_dive->majoration;
        $this->incident_dive->calculateStops();
        if (!array_key_exists(3, $this->incident_dive->deco_stops) || $this->incident_dive->deco_stops[3] <= 2)
            $this->incident_dive->deco_stops[3] = 2;
        $this->incident_dive->calculateAscentProfile();
        // Erase the fake depth for graph purposes
        $this->incident_dive->max_depth = $this->incident_dive->depth_when_ascent_starts;

        $this->incident_dive->incident_type = 'quick ascent';

        // Copy GPS to main dive
        $this->GPS = $this->incident_dive->GPS;
    }

  /*
   * Generates the dots for the graph
   * The structure of each dot is x (timestamp in ms), y (meters), label to display, position of label
   *
   */
    public function generateDots() {
        $this->dots = [];
        $datetime = clone $this->start_time;
        if ($this->majoration && !$this->incident_type) {
            // Second dive, add more info
            $label = implode('<br />', [
                dc_render_time($datetime),
                trans('piratmac.staycalmanddive::lang.graph.majoration', ['majoration' => $this->majoration]),
                trans('piratmac.staycalmanddive::lang.graph.residual_nitrogen', ['residual_nitrogen' => $this->residual_nitrogen]),
            ]);
            $this->dots[] = [clone $datetime, 0, $label, 'final'];
        }
        else
            $this->dots[] = [clone $datetime, 0, dc_render_time($datetime), 'above'];

        if ($this->incident_type == 'interrupted stop') // For new stops, descent is instantaneous + don't render so many points
            $this->dots[] = [clone $datetime, $this->max_depth, dc_render_depth($this->max_depth), 'below'];
        elseif ($this->incident_type == 'quick ascent') {
            $this->dots[] = [clone $datetime, $this->max_depth, dc_render_depth($this->max_depth), 'below'];
            $datetime = clone $this->time_when_ascent_starts;
            $this->dots[] = [clone $datetime, $this->depth_when_ascent_starts, dc_render_depth($this->depth_when_ascent_starts).' <br /> '.dc_render_minutes($this->duration), 'below'];
        }
        else {
            $this->dots[] = [clone $datetime->addSeconds(30), $this->max_depth, dc_render_depth($this->max_depth), 'below'];
            $datetime = clone $this->time_when_ascent_starts;
            $this->dots[] = [clone $datetime, $this->depth_when_ascent_starts, dc_render_depth($this->depth_when_ascent_starts).' <br /> '.dc_render_minutes($this->duration), 'below'];
        }

        if ($this->deco_stops) {
            $depths = array_keys($this->deco_stops);
            rsort($depths, SORT_NUMERIC);
            if (!$this->incident_type || $this->incident_type != 'interrupted stop') // Don't render the first point, it's already rendered above
                $this->dots[] = [clone $datetime->addSeconds($this->time_to_first_stop*60), max($depths), '', 'above'];
            foreach ($depths as $depth) {
                $stop_duration = $this->deco_stops[$depth];
                $this->dots[] = [clone $datetime->addMinutes($stop_duration), $depth, dc_render_depth($depth).' <br /> '.dc_render_minutes($stop_duration), 'below'];
                $next_stop = array_filter($depths, function ($val) use ($depth) { return $val < $depth; });
                $next_stop = reset($next_stop)+0; // $next_stop = false if there is no further stop
                if ($next_stop)
                    $this->dots[] = [clone $datetime->addSeconds(30), $next_stop, '', 'above'];
            }
        }

        if (isset($this->incident_dive)) {
            $this->dots[] = [$this->end_time, 0, dc_render_time($this->end_time), 'aboveleft']; // Don't display GPS, ..., after the incident
            $this->dots = array_map(function ($v) { $v[0] = $v[0]->timestamp*1000; return $v; }, $this->dots);

            $incident_dots = $this->incident_dive->generateDots();
            $this->dots = array_merge($this->dots, $this->incident_dive->generateDots());
        }
        else {
            if ($this->majoration && !$this->incident_type)
                $end_label = implode('<br />', [
                    dc_render_time(clone($this->end_time)),
                    trans('piratmac.staycalmanddive::lang.graph.total_ascent_time', ['duration' => dc_render_minutes($this->total_ascent_time)]),
                ]);
            else
                $end_label = implode('<br />', [
                    dc_render_time(clone($this->end_time)),
                    trans('piratmac.staycalmanddive::lang.graph.GPS', ['GPS' => $this->GPS]),
                    trans('piratmac.staycalmanddive::lang.graph.total_ascent_time', ['duration' => dc_render_minutes($this->total_ascent_time)]),
                ]);
            $this->dots[] = [$this->end_time, 0, $end_label, 'final'];
            $this->dots = array_map(function ($v) { $v[0] = $v[0]->timestamp*1000; return $v; }, $this->dots);
        }


       return $this->dots;
    }

  /*
   * Prepares the data for the table displayed next to graphs
   * Key is label, value is the value to display
   */
    public function prepareDataTable() {
        $data_table = [];
        // Depth
        $data_table[] = ['dive.depth_calculation'        => ''];
        $data_table[] = ['dive.max_depth'                => dc_render_depth($this->max_depth)];

        if ($this->nitrox_ratio) {
            $data_table[] = ['dive.nitrox_equivalent_depth'    => dc_render_depth($this->MN90Tables->depth_for_calculation)];
            $data_table[] = ['dive.nitrox_max_operating_depth' => dc_render_depth($this->nitrox_maximum_operating_depth)];
        }
        $data_table[] = ['dive.max_depth_table'    => dc_render_depth($this->MN90Tables->depth)];


        // Duration
        $data_table[] = ['dive.duration_calculation'     => ''];
        $data_table[] = ['dive.start_time'               => dc_render_time($this->start_time)];
        $data_table[] = ['dive.duration'                 => dc_render_minutes($this->duration)];

        if ($this->majoration) {
            $data_table[] = ['interval.majoration'           => dc_render_minutes($this->majoration)];
            $data_table[] = ['dive.duration_with_majoration' => dc_render_minutes($this->duration_for_calculation)];
        }
        $data_table[] = ['dive.duration_in_table'        => dc_render_minutes($this->MN90Tables->duration)];

        // Stops
        $data_table[] = ['dive.stops_calculation'              => ''];

        if (!$this->incident_dive) {
            $data_table[] = ['dive.depth_when_ascent_starts' => dc_render_depth($this->depth_when_ascent_starts)];
            $data_table[] = ['dive.time_when_ascent_starts'  => dc_render_time($this->time_when_ascent_starts)];

            foreach ($this->deco_stops as $depth => $duration) {
                $label = str_replace('%depth%', $depth, trans('piratmac.staycalmanddive::lang.dive.stop_depth'));
                $data_table[] = [$label             => dc_render_minutes($duration)];
            }

            $data_table[] = ['dive.total_ascent_time'        => dc_render_minutes($this->total_ascent_time)];
            $data_table[] = ['dive.surface_time'             => dc_render_time($this->end_time)];
        }


        else {
            $data_table[] = ['dive.before_incident'          => ''];
            $data_table[] = ['dive.depth_when_ascent_starts' => dc_render_depth($this->depth_when_ascent_starts)];
            $data_table[] = ['dive.incident_time'            => dc_render_time($this->time_when_ascent_starts)];
            foreach ($this->deco_stops as $depth => $duration) {
                $label = str_replace('%depth%', $depth, trans('piratmac.staycalmanddive::lang.dive.stop_depth'));
                $data_table[] = [$label             => dc_render_minutes($duration)];
            }
            $data_table[] = ['dive.total_ascent_time'        => dc_render_minutes($this->total_ascent_time)];
            $data_table[] = ['dive.surface_time'             => dc_render_time($this->end_time)];

            $data_table[] = ['dive.after_incident'           => ''];
            $data_table[] = ['dive.max_depth'                => dc_render_depth($this->incident_dive->max_depth)];
            if ($this->quick_ascent) {
                $data_table[] = ['dive.duration'                 => dc_render_minutes($this->incident_dive->duration)];
                $data_table[] = ['dive.duration_for_calculation' => dc_render_minutes($this->incident_dive->duration_for_calculation)];
                $data_table[] = ['dive.max_depth'                => dc_render_depth($this->incident_dive->max_depth)];
                $data_table[] = ['dive.max_depth_table'          => dc_render_depth($this->incident_dive->MN90Tables->depth)];
                $data_table[] = ['dive.time_when_ascent_starts'  => dc_render_time($this->incident_dive->time_when_ascent_starts)];
            }
            foreach ($this->incident_dive->deco_stops as $depth => $duration) {
                $label = str_replace('%depth%', $depth, trans('piratmac.staycalmanddive::lang.dive.stop_depth'));
                $data_table[] = [$label             => dc_render_minutes($duration)];
            }
            $data_table[] = ['dive.total_ascent_time'        => dc_render_minutes($this->incident_dive->total_ascent_time)];
            $data_table[] = ['dive.surface_time'             => dc_render_time($this->incident_dive->end_time)];
        }
        if (!$this->majoration)
            $data_table[] = ['dive.GPS_at_end'           => $this->GPS];

        foreach ($data_table as $id =>$val) {
            foreach ($val as $key => $value) {
                if (substr($key, 0, 5) == 'dive.') {
                    $key_label = trans('piratmac.staycalmanddive::lang.'.$key);
                    $this->data_table[$id] = [$key_label => $value];
                }
                elseif (substr($key, 0, 9) == 'interval.') {
                    $key_label = trans('piratmac.staycalmanddive::lang.'.$key);
                    $this->data_table[$id] = [$key_label => $value];
                }
                else
                    $this->data_table[$id] = [$key => $value];
            }
        }

        return $this->data_table;
    }

    public function getGasAtDepthAttribute() {
        if ($this->nitrox_ratio)
            return ['type' => 'nitrox', 'O2_ratio' => $this->nitrox_ratio];
        else
            return ['type' => 'air', 'O2_ratio' => 21];
    }
    public function getGasAtStopsAttribute() {
        if ($this->use_oxygen)
            return ['type' => 'oxygen', 'O2_ratio' => 100];
        else
            return ['type' => 'air', 'O2_ratio' => 21];
    }
    public function getAtmosphericPressureAttribute() {
        return property_exists($this, 'atmospheric_pressure')?$this->atmospheric_pressure:1;
    }


}
