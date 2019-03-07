<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports\variables;

use kuriousagency\bigreports\BigReports;
use kuriousagency\bigreports\services\BigReportsService;

use Craft;
use yii\di\ServiceLocator;

/**
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class BigReportsVariable extends ServiceLocator
{
    // Public Methods
    // =========================================================================

	public function __construct($config = [])
	{
		$components = [
			'service' => BigReportsService::class,
		];
		
		$config['components'] = $components;

		parent::__construct($config);
	}
}
