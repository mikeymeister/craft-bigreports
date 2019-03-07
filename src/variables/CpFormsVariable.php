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

use Craft;
use yii\base\Behavior;

/**
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class CpFormsVariable extends Behavior
{
    // Public Methods
    // =========================================================================

	public function forms($type, $options=[])
	{
		return BigReports::$plugin->service->getFormsMacro($type, $options);
	}
}
