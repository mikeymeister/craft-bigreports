<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports\records;

use kuriousagency\bigreports\BigReports;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 */
class Report extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bigreports}}';
    }
}
