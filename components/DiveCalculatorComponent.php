<?php namespace Piratmac\Staycalmanddive\Components;

use Cms\Classes\ComponentBase;
use Piratmac\Staycalmanddive\Controllers\DiveCalculator as DiveCalculator;
use Piratmac\Staycalmanddive\Models\DiveLog as DiveLog;

class DiveCalculatorComponent extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Dive calculator component',
        ];
    }

    public function onRun()
    {
        // Build a back-end form with the context of 'frontend'
        $formController = new DiveCalculator();
        $formController->create('frontend');

        // Append the formController to the page
        $this->page['form'] = $formController;


        $this->addCss('/modules/system/assets/ui/storm.css', 'core');
        //$this->addJs('/modules/backend/assets/js/winter-min.js', 'core');
        //$this->addJs('/modules/backend/assets/js/winter.flyout.js', 'core');
        //$this->addJs('/modules/backend/assets/js/winter.tabformexpandcontrols.js', 'core');
        //$this->addJs('/modules/backend/widgets/form/assets/js/winter.form.js', 'core');
        $this->addJs('/modules/system/assets/js/framework.js', 'core');
        $this->addJs('/modules/system/assets/ui/storm-min.js', 'core');
    }

    public function onCalculate() {
        $diveLog = DiveLog::make();
        $diveLog->fill(post('DiveLog'));
        $diveLog->validate();
    }
}
