<?php namespace Piratmac\Staycalmanddive\Models;

use Model;
use \Winter\Storm\Argon\Argon;
use Winter\Storm\Exception\ValidationException as ValidationException;
use Piratmac\Staycalmanddive\Classes\MN90Tables as MN90Tables;

/**
 * Entry Model
 */
class Interval extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    //public $table = 'piratmac_dive_log';

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [
        'use_interval_duration'  => 'boolean',
        'duration'  => 'numeric|between:1,720|required_if:use_interval_duration,1',
        'use_oxygen'  => 'boolean',
        'oxygen_when' => 'in:start,end|required_if:use_oxygen,1',
        'oxygen_duration'  => 'numeric',
    ];

    protected $casts = [
        'use_interval_duration'  => 'boolean',
        'duration'  => 'integer',
        'use_oxygen'  => 'boolean',
        'oxygen_when' => 'string',
        'oxygen_duration' => 'integer',
    ];

    public $fillable = ['use_interval_duration', 'duration', 'use_oxygen', 'oxygen_when', 'oxygen_duration'];

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

    public $data_table = [];

    public function setDives($dive1, $dive2_start_time) {
        $this->start_GPS    = $dive1->GPS;
        $this->start_time   = isset($dive1->incident_dive)?$dive1->incident_dive->end_time:$dive1->end_time;
        $this->MN90Tables   = new MN90Tables();
        $this->atmospheric_pressure = $dive1->atmospheric_pressure;

        if ($this->use_interval_duration) {
            $this->end_time = (clone $this->start_time)->addMinutes($this->duration);
        }
        else {
            $this->end_time = \Winter\Storm\Argon\Argon::make($dive2_start_time);
            $this->duration = $this->end_time->diffInMinutes($this->start_time);
            if ($this->duration < 0)
                throw new ValidationException(['DiveLog[dive2][start_time]' => trans('piratmac.staycalmanddive::lang.calculation.errors.dives_overlap')]);
        }
        $this->consecutive_dives = ($this->duration <= 15);
    }

    public function calculateNitrogenElimination() {
        // Determine residual nitrogen
        $this->MN90Tables->getResidualNitrogen($this->start_GPS, $this->duration, ['duration' => $this->oxygen_duration, 'when' => $this->oxygen_when]);
        $this->nitrogen_elimination = $this->MN90Tables->nitrogen_elimination;
        $this->residual_nitrogen = isset($this->nitrogen_elimination['gas2'])?$this->nitrogen_elimination['gas2']['end_nitrogen']:$this->nitrogen_elimination['gas1']['end_nitrogen'];
    }

    public function calculateMajoration($dive2) {
        // Equivalent depth for altitude & Nitrox
        $equivalent_depth = $this->MN90Tables->adjustDepthForNitroxAndAltitude($dive2->max_depth, $dive2->gas_at_depth, $dive2->atmospheric_pressure, $dive2->air_nitrogen_ratio);

        $this->MN90Tables->getMajoration($this->residual_nitrogen, $equivalent_depth);
        $this->majoration = $this->MN90Tables->majoration;
        $this->tableII_residual_nitrogen = $this->MN90Tables->tableII_residual_nitrogen;
        $this->tableII_depth = $this->MN90Tables->tableII_depth;
    }

  /*
   * Prepares the data for the table displayed next to graphs
   * Key is label, value is the value to display
   */
    public function prepareDataTable() {
        $data_table = [];
        // Nitrogen elimination

        if (count($this->nitrogen_elimination) == 1) {
            $data_table[] = ['interval.nitrogen_elimination'        => ''];
            $data_table[] = ['interval.GPS_at_start'                => $this->start_GPS];
            $data_table[] = ['interval.duration'                    => dc_render_minutes($this->duration)];
            $data_table[] = ['interval.duration_in_table'           => dc_render_minutes($this->nitrogen_elimination['gas1']['table_duration'])];
            $data_table[] = ['interval.residual_nitrogen'           => $this->tableII_residual_nitrogen];
        }
        else {
            $data_table[] = ['interval.nitrogen_elimination'        => ''];
            $data_table[] = ['interval.GPS_at_start'                => $this->start_GPS];

            foreach ($this->nitrogen_elimination as $order => $gas) {
                $label = ($order=='gas1')?'first_gas':'second_gas';
                $data_table[] = ['interval.'.$label                     => ''];
                $data_table[] = ['interval.gas_breathed'                => trans('piratmac.staycalmanddive::lang.interval.gas_breathed_'.$gas['type'])];
                $data_table[] = ['interval.duration'                    => dc_render_minutes($gas['actual_duration'])];
                $data_table[] = ['interval.duration_in_table'           => dc_render_minutes($gas['table_duration'])]; //TODO: KO
                $data_table[] = ['interval.residual_nitrogen'           => $gas['end_nitrogen']];
            }
            //$data_table[] = ['interval.residual_nitrogen'           => $this->tableII_residual_nitrogen];
        }

        $data_table[] = ['interval.majoration_calculation'      => ''];
        //$data_table[] = ['interval.residual_nitrogen_in_table'  => $this->tableII_residual_nitrogen];
        $data_table[] = ['interval.depth_in_table'              => dc_render_depth($this->MN90Tables->tableII_depth)];
        $data_table[] = ['interval.majoration'                  => dc_render_minutes($this->majoration)];


        foreach ($data_table as $id =>$val) {
            foreach ($val as $key => $value) {
                if (substr($key, 0, 9) == 'interval.') {
                    $key_label = trans('piratmac.staycalmanddive::lang.'.$key);
                    $this->data_table[$id] = [$key_label => $value];
                }
                else
                    $this->data_table[$id] = [$key => $value];
            }
        }

        return $this->data_table;
    }
}
