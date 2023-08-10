<?php namespace Piratmac\Staycalmanddive\Models;

use Model;

/**
 * Entry Model
 */
class DiveLog extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    // public $table = 'piratmac_staycalmanddive_divelogs';

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [
        'multiple_dives'  => 'boolean',
        'advanced_parameters'  => 'boolean',
        'use_nitrox'  => 'boolean',
        'max_oxygen_pressure' => 'numeric|between:1.4,1.6|required_if:use_nitrox,1',
        'use_oxygen'  => 'boolean',
    ];

    protected $casts = [
        'multiple_dives'  => 'boolean',
        'advanced_parameters'  => 'boolean',
        'use_nitrox'  => 'boolean',
        'max_oxygen_pressure' => 'float',
        'use_oxygen'  => 'boolean',
    ];

    public $fillable = ['multiple_dives', 'advanced_parameters', 'use_nitrox', 'max_oxygen_pressure', 'use_oxygen'];


    /**
     * @var array Relations
     */
    public $hasOne = [
        'interval' => ['Piratmac\Staycalmanddive\Models\Interval'],
        'dive1' => ['Piratmac\Staycalmanddive\Models\Dive'],
        'dive2' => ['Piratmac\Staycalmanddive\Models\Dive'],
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
    public $dots = [];

    public function calculateDecompression ($form_data) {
        $this->fill($form_data);
        $this->validate();
        $this->calculationWarnings['divelog'] = $this->calculationWarnings;

        $this->dive1 = Dive::make();
        $this->dive1->fill($form_data['dive1']);
        $this->dive1->validate();
        $this->dive1->beforeCalculate($this);
        $this->dive1->calculateDecompression();


        $this->calculationWarnings['dive1'] = $this->dive1->calculationWarnings;

        if ($this->multiple_dives) {
            $this->interval = Interval::make();
            $this->interval->fill($form_data['interval']);
            $this->interval->validate();
            $this->interval->setDives($this->dive1, $form_data['dive2']['start_time']);

            $this->dive2 = Dive::make();
            $this->dive2->fill($form_data['dive2']);
            if ($this->interval->use_interval_duration)
                $this->dive2->start_time = $this->interval->end_time;

            $this->dive2->validate();


            if ($this->interval->consecutive_dives) {
                /* When dives are consecutive, the calculation becomes trickier
                 * We will calculate a virtual dive to determine the stops to make
                 * That virtual dive has the entire duration of both dives, and the depth of the deeper one
                 * Then we'll merge the stops of that virtual dive to dive 2
                 */

                // First, get & validate the form data to dive 2
                $this->dive2->beforeCalculate($this);
                $this->dive2->calculateDecompression();

                $virtual_dive = clone $this->dive2;
                $virtual_dive->fill([
                    'start_time' => $this->dive2->start_time->addMinutes(-$this->dive1->duration),
                    'duration' => $this->dive1->duration + $this->dive2->duration,
                    'max_depth' => max($this->dive1->max_depth, $this->dive2->max_depth)
                ]);
                $virtual_dive->beforeCalculate($this);
                $virtual_dive->calculateDecompression();

                // Merge that virtual dive to the real one
                $this->dive2->MN90Tables = clone $virtual_dive->MN90Tables;
                $this->dive2->deco_stops = $virtual_dive->deco_stops;
                $this->dive2->GPS = $virtual_dive->GPS;
                if ($virtual_dive->incident_dive)
                    $this->dive2->incident_dive = clone $virtual_dive->incident_dive;

                unset($virtual_dive);
                $this->dive2->calculateAscentProfile();
            }
            else {
                // Get majoration for 2nd dive
                $this->interval->calculateNitrogenElimination();
                $this->interval->calculateMajoration($this->dive2);

                // Create second dive
                $this->dive2->majoration = $this->interval->majoration;
                $this->dive2->residual_nitrogen = $this->interval->tableII_residual_nitrogen;
                $this->dive2->beforeCalculate($this);
                $this->dive2->calculateDecompression();
            }

            $this->calculationWarnings['dive2'] = $this->dive2->calculationWarnings;
        }

    }

    public function generateDots() {
        $this->dots = [];
        $this->dots[] = $this->dive1->generateDots();

        if ($this->multiple_dives) {
            //$this->dots += $this->interval->generateDots();
            $this->dots[] = $this->dive2->generateDots();
        }
        return $this->dots;
    }
}


if (!function_exists('dc_render_duration')) {
    /*
     * Rendering function: renders a given duration in a nice format (either direct duration, or converted to hours:minutes)
     * Examples: 75 is converted to "75 min (01:15)", 25 to "25 min"
     */
    function dc_render_minutes ($duration) {
        if (is_numeric($duration))
            $duration = round((float)$duration);
        else
            throw new Exception('Wrong data provided: not numeric');

        $hours = floor($duration / 60);
        $minutes = $duration - $hours*60;

        if ($duration > 60)
            return trans('piratmac.staycalmanddive::lang.various.duration_format_hour_minutes', ['duration' => $duration, 'minutes' => $minutes, 'hours' => $hours]);
        return trans('piratmac.staycalmanddive::lang.various.duration_format_minutes', ['minutes' => $duration]);
    }

    /*
     * Rendering function: renders a depth, based on lang file
     * Example: 75 is converted to "75 m" in English
     */
    function dc_render_depth ($depth) {
      return trans('piratmac.staycalmanddive::lang.various.depth_value', ['depth' => $depth]);
    }

    /*
     * Rendering function: renders a time, taking into account user language
     */
    function dc_render_time ($time) {
      return $time->format('H:i');
    }
}
