<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports\controllers;

use kuriousagency\bigreports\BigReports;

use kuriousagency\bigreports\models\Report as ReportModel;
use vova07\console\ConsoleRunner;

use Craft;
use craft\web\Controller;
use craft\helpers\DateTimeHelper;

/**
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex()
    {
		return $this->renderTemplate('bigreports/index', [
			'types' => BigReports::$plugin->service->getTypes(),
			'reports' => BigReports::$plugin->service->getAllReports(),
		]);
    }

    public function actionNew($type)
    {
		$report = new ReportModel();
		$report->type = $type;

		return $this->renderTemplate('bigreports/edit', [
			'report' => $report,
			'options' => BigReports::$plugin->service->getOptions($report)
		]);
	}
	
	public function actionEdit($id)
	{
		$report = BigReports::$plugin->service->getReportById($id);

		if (!$report) {
			throw new NotFoundHttpException('Report not found');
		}

		return $this->renderTemplate('bigreports/edit', [
			'report' => $report,
			'options' => BigReports::$plugin->service->getOptions($report)
		]);
	}

	public function actionSave()
	{
		$this->requirePostRequest();

		$request = Craft::$app->getRequest();

		$id = $request->getParam('id');
		$report = BigReports::$plugin->service->getReportById($id);

		if (!$report) {
			$report = new ReportModel();
		}

		$options = $request->getParam('options');

		foreach($options as $key => $option)
		{
			if (is_array($option) && array_key_exists('date', $option)) {
				$options[$key] = DateTimeHelper::toIso8601($option);
			}
		}

		$user = Craft::$app->getUser()->getIdentity();

		$report->name = $request->getParam('name');
		$report->type = $request->getParam('type');
		$report->options = $options;
		$report->email = $request->getParam('email', $user->email);

		BigReports::$plugin->service->saveReport($report);

		$this->redirect("bigreports");
	}

	public function actionExport($id)
	{
		if ($id > 0) {
			$path = Craft::getAlias("@root") . "/craft";
			$console = new ConsoleRunner(['file' => $path]);
			$console->run('bigreports/export ' . $id);
		}

		//BigReports::$plugin->service->exportCsv($id);

		Craft::$app->getSession()->setNotice(Craft::t('bigreports', 'Report exporting...'));
		$this->redirect("bigreports");
	}

	public function actionDelete()
	{
		$this->requirePostRequest();
		$this->requireAcceptsJson();

		$id = Craft::$app->getRequest()->getRequiredBodyParam('id');

		BigReports::$plugin->service->deleteReportById($id);

		return $this->asJson(['success' => true]);
	}
}
