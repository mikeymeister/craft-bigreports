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
    // Public Methods
    // =========================================================================

    /**
     * Handle big-reports/export console commands
     *
     * @return mixed
     */
    public function actionIndex()
    {
		$params = Craft::$app->getRequest()->getParams();

		$id = $params[1];

		BigReports::$plugin->service->exportCsv($id);

		return true;
    }

}
