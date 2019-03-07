<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports\models;

use kuriousagency\bigreports\BigReports;
use kuriousagency\bigreports\records\Report as ReportRecord;

use Craft;
use craft\base\Model;
use craft\validators\UniqueValidator;
use craft\helpers\Json;
use craft\helpers\DateTimeHelper;

/**
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class Report extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
	public $id;
	public $siteId;
	public $name;
	public $type;
	public $options;
	public $email;
	public $dateExported;

    // Public Methods
	// =========================================================================
	
	public function getParsedOptions()
	{
		$options = Json::decode($this->options);

		if($options) {	
			foreach ($options as $key => $option)
			{
				if (preg_match('/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/', $option)) {
					$options[$key] = DateTimeHelper::toDateTime($option);
				}	
			}
		}
		
		return $options;
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'email', 'type'], 'required']
        ];
    }
}
