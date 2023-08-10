<?php namespace Piratmac\Staycalmanddive;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            '\Piratmac\Staycalmanddive\Components\DiveCalculatorComponent' => 'diveCalculatorComponent'
        ];
    }

    public function registerSettings()
    {
    }
}
