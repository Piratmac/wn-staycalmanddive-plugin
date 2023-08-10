<?php namespace Piratmac\Staycalmanddive\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Winter\Storm\Exception\ValidationException as ValidationException;
use Piratmac\Staycalmanddive\Models\DiveLog as DiveLog;
use Piratmac\Staycalmanddive\Models\Dive as Dive;
use Piratmac\Staycalmanddive\Models\Interval as Interval;

/**
 * Entries Back-end Controller
 */
class DiveCalculator extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
    ];

    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Piratmac.StayCalmAndDive', 'main-menu-item', 'divecalculator');

        $overallMenu = BackendMenu::getActiveMainMenuItem();
        $context = BackendMenu::getContext();
        $this->breadcrumbTitle = trans($overallMenu->label);
    }


    public function index ()
    {
        //$this->pageTitle = "Dive calc test";
        $this->pageTitle = trans(BackendMenu::getActiveMainMenuItem()->label);
        $this->addCss('/plugins/piratmac/staycalmanddive/assets/css/dive-calculator.css');

        // Build a back-end form
        $this->create();

    }

    public function onCalculate ()
    {
        $calculationWarnings = [];

        $diveLog = DiveLog::make();
        $diveLog->calculateDecompression(post('DiveLog'));
        $diveLog->generateDots();

        $dots = [];
        $dots_labels = [];
        $data_table = [];
        for ($i = 0; $i < count($diveLog->dots); $i++) {
            $dots[] = json_encode(array_map(function ($v) { return [$v[0], $v[1]];}, $diveLog->dots[$i]));
            $dots_labels[] = json_encode($diveLog->dots[$i]);
            $data_table['dive'.($i+1)] = $diveLog->{'dive'.($i+1)}->prepareDataTable();
        }
        if (count($diveLog->dots) > 1 && !$diveLog->interval->consecutive_dives)
            $data_table['interval'] = $diveLog->interval->prepareDataTable();

        $this->vars['dots'] = $dots;
        $this->vars['dots_labels'] = $dots_labels;
        $this->vars['data_table'] = $data_table;
        $this->vars['calculationWarnings'] = $diveLog->calculationWarnings;

        return [
            '#dive_graph' => $this->makePartial('dive-graph')
        ];
    }
}
