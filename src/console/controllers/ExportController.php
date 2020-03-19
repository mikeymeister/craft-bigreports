<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports\console\controllers;

use kuriousagency\bigreports\BigReports;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Export Command
 *
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class ExportController extends Controller
{
	public $defaultAction = 'export';
	
	public $startDate;
	public $endDate;
	public $id;
	public $email;

    // Public Methods
	// =========================================================================
	
	public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'export') {
            $options[] = 'startDate';
			$options[] = 'endDate';
			$options[] = 'id';
			$options[] = 'email';
        }

        return $options;
	}
	
	public function optionAliases()
    {
        $aliases = parent::optionAliases();
        $aliases['i'] = 'id';
		$aliases['s'] = 'startDate';
		$aliases['e'] = 'endDate';
		$aliases['m'] = 'email';

        return $aliases;
    }

    /**
     * Handle big-reports/export console commands
     *
     * @return mixed
     */
    public function actionExport()
    {
		if (!$this->id) {
			throw new Exception('Missing required arguments: id');
		}

		BigReports::$plugin->service->exportCsv($this->id, $this->startDate, $this->endDate, $this->email);

		return true;
    }

}
