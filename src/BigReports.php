<?php
/**
 * Big Reports plugin for Craft CMS 3.x
 *
 * Run reports on large data sets.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\bigreports;

use kuriousagency\bigreports\services\BigReportsService;
use kuriousagency\bigreports\models\Settings;
use kuriousagency\bigreports\variables\BigReportsVariable;
use kuriousagency\bigreports\variables\CpFormsVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\events\PluginEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class BigReports
 *
 * @author    Kurious Agency
 * @package   BigReports
 * @since     1.0.0
 *
 * @property  BigReportsService $bigReportsService
 */
class BigReports extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var BigReports
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '0.0.1';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
		self::$plugin = $this;
		
		$this->setComponents([
			'service' => BigReportsService::class,
		]);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'kuriousagency\bigreports\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
				$event->rules['bigreports'] = 'bigreports/default/index';
				$event->rules['bigreports/<type:[-\w]+>/new'] = 'bigreports/default/new';
				$event->rules['bigreports/edit/<id:\d+>'] = 'bigreports/default/edit';
				$event->rules['bigreports/save'] = 'bigreports/default/save';
				$event->rules['bigreports/export/<id:\d+>'] = 'bigreports/default/export';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
				$variable->set('bigreports', BigReportsVariable::class);
				$variable->attachBehavior('forms', CpFormsVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
		);
		
		Event::on(
			UserPermissions::class,
			UserPermissions::EVENT_REGISTER_PERMISSIONS,
			function(RegisterUserPermissionsEvent $event) {
				$event->permissions[$this->name] = [
					'bigreports-create' => ['label' => Craft::t('bigreports', 'Create Reports')],
					'bigreports-edit' => ['label' => Craft::t('bigreports', 'Edit Reports')],
				];
        });

        Craft::info(
            Craft::t(
                'bigreports',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
	// =========================================================================
	
	/**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'bigreports/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

}
