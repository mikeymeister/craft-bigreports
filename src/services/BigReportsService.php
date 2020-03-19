<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports\services;

use kuriousagency\bigreports\BigReports;
use kuriousagency\bigreports\models\Report as ReportModel;
use kuriousagency\bigreports\records\Report as ReportRecord;

use Craft;
use craft\base\Component;
use craft\web\View;
use craft\db\Query;
use craft\mail\Message;
use craft\helpers\StringHelper;
use craft\helpers\FileHelper;
use craft\helpers\Template as TemplateHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use League\Csv\Writer;

/**
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class BigReportsService extends Component
{
	private $_data = [];
	
	// Public Methods
	// =========================================================================
	
	public function getReportById($id)
	{
		$result = $this->_createReportQuery()
			->where(['id' => $id])
			->one();

		return new ReportModel($result);
	}

	public function getAllReports()
	{
		$reports = [];
		$results = $this->_createReportQuery()->all();

		foreach ($results as $row) {
			$reports[] = new ReportModel($row);
		}

		return $reports;
	}

	public function saveReport(ReportModel $model)
	{
		if ($model->id) {
			$record = ReportRecord::findOne($model->id);

			if (!$record->id) {
				throw new Exception(Craft::t('bigreports', 'No report exists with the ID "{id}"', ['id' => $model->id]));
			}
		} else {
			$record = new ReportRecord();
		}

		if (!$model->validate()) {
			Craft::info('Report could not save due to validation error.', __METHOD__);
			return false;
		}

		$record->siteId = Craft::$app->sites->currentSite->id;
		$record->name = $model->name;
		$record->type = $model->type;
		$record->options = $model->options;
		$record->email = $model->email;
		$record->dateExported = $model->dateExported;

		$record->save(false);

		$model->id = $record->id;

		return true;
	}

	public function deleteReportById($id): bool
	{
		$record = ReportRecord::findOne($id);

		if ($record) {
			return (bool)$record->delete();
		}

		return false;
	}

	public function exportCsv($id, $startDate=null, $endDate=null, $email=null)
	{
		if (!$id) {
			Craft::$app->end();
		}

		$report = $this->getReportById($id);
		$report->dateExported = new \DateTime();

		$this->saveReport($report);

		//override options
		$options = (object) Json::decodeIfJson($report->options);
		if ($startDate) {
			$options->startDate = DateTimeHelper::toIso8601(new \DateTime($startDate));
		}
		if ($endDate) {
			$options->endDate = DateTimeHelper::toIso8601(new \DateTime($endDate));
		}
		$report->options = Json::encode($options);
		if ($email) {
			$report->email = $email;
		}

		$data = $this->parseReport($report);

		FileHelper::createDirectory(Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'bigreports');
		$fileName = date("YmdHis").".csv";
		$tempFile = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'bigreports' . DIRECTORY_SEPARATOR . $fileName;

		if (($handle = fopen($tempFile, 'wb')) === false) {
			throw new Exception('Could not create temp file: ' . $tempFile);
		}

		fclose($handle);

		$csv = Writer::createFromPath(new \SplFileObject($tempFile, 'a+'), 'w');

		if ( isset($data['columns']) ) {
			$csv->insertOne($data['columns']);
		}

		if (isset($data['rows'])) {
			foreach ($data['rows'] as $row) {
				$csv->insertOne($row);
			}
		}

		$this->emailReport($fileName, $report->email);

		unlink($tempFile);
	}

	public function parseReport($report)
	{
		$result = Craft::$app->view->renderString($this->getTemplate($report));
		$result = StringHelper::collapseWhitespace($result);
		$result = Json::decode($result);
		return $result;
	}

	public function columns($data)
	{
		$this->_data['columns'] = $data;
	}

	public function row($data)
	{
		$this->_data['rows'][] = $data;
	}

	public function getData()
	{
		return TemplateHelper::raw(Json::encode($this->_data));
	}

	public function getTypes()
	{
		$types = [];
		// $path = BigReports::$plugin->getSettings()->templatePath;
		$path = false;
		if (!$path) {
			$path = '_reports';
		}

		$path = Craft::$app->getPath()->getSiteTemplatesPath()."/".$path;

		try {
			$folders = FileHelper::findDirectories(FileHelper::normalizePath($path));
		} catch (\Exception $e) {
			Craft::warning('Big Reports folder not found','bigreports');
			Craft::$app->session->setError($e->getMessage());
			return false;
		}

		foreach($folders as $folder){
			$types[] = pathinfo($folder, PATHINFO_BASENAME);
		}

		asort($types);

		return $types;
	}

	public function getOptions($report)
	{
		$templatePath = BigReports::$plugin->getSettings()->templatePath;
		if (!$templatePath) {
			$templatePath = '_reports';
		}
		
		$path = $templatePath."/".$report->type;

		$view = Craft::$app->getView();
		$oldTemplateMode = $view->getTemplateMode();
		$view->setTemplateMode(View::TEMPLATE_MODE_SITE);

		if ($view->doesTemplateExist($path."/options")) {
			$options = $view->renderTemplate($path."/options", [
				'options' => $report->parsedOptions,
				'report' => $report,
			]);

			$view->setTemplateMode($oldTemplateMode);

			return $options;
		}

		$view->setTemplateMode($oldTemplateMode);
		return null;
	}

	public function getTemplate($report)
	{
		$templatePath = BigReports::$plugin->getSettings()->templatePath;
		if (!$templatePath) {
			$templatePath = '_reports';
		}
		$path = $templatePath."/".$report->type;

		$view = Craft::$app->getView();
		$oldTemplateMode = $view->getTemplateMode();
		$view->setTemplateMode(View::TEMPLATE_MODE_SITE);

		$result = $view->renderTemplate($path."/results", [ 
			'options' => $report->parsedOptions,
		]);

		$view->setTemplateMode($oldTemplateMode);

		return $result;
	}

	public function emailReport($filename, $email)
    {
		$templatePath = BigReports::$plugin->getSettings()->emailPath;

		$view = Craft::$app->getView();
		$oldTemplateMode = $view->getTemplateMode();
		$view->setTemplateMode($view::TEMPLATE_MODE_SITE);

		$emailArray = explode(",",$email);

		$newEmail = new Message();
		$newEmail->setTo($emailArray);
		$newEmail->setFrom(Craft::parseEnv(Craft::$app->systemSettings->getEmailSettings()->fromEmail));
		$newEmail->setSubject('Report');

		if($templatePath && $view->doesTemplateExist($templatePath)) {
			$body = $view->renderTemplate($templatePath, $renderVariables);
			$newEmail->setHtmlBody($body);
		}

		$file = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'bigreports' . DIRECTORY_SEPARATOR . $filename;
		
		if (file_exists($file)) {
			$newEmail->attach($file, array(
				'fileName' => $filename,
			));
		}

		if (!Craft::$app->getMailer()->send($newEmail)) {
			
			Craft::error(Craft::t('bigreports', 'Email Error'), __METHOD__);
			
			$view->setTemplateMode($oldTemplateMode);

			return false;
		}
		
		$view->setTemplateMode($oldTemplateMode);

		return true;
	}

	public function getFormsMacro($type, $options)
	{
		$view = Craft::$app->getView();

		$path = $view->getTemplatesPath();

		$view->setTemplatesPath(Craft::$app->path->getCpTemplatesPath());

		$macro = $view->renderTemplateMacro('_includes/forms', $type, [$options]);

		$view->setTemplatesPath($path);

		return TemplateHelper::raw($macro);
	}
	
	// Private Methods
    // =========================================================================

	private function _createReportQuery()
	{
		return (new Query())
			->select([
				'id',
				'name',
				'type',
				'options',
				'email',
				'dateExported',
			])
			->from(['{{%bigreports}}']);
	}

	
}
