<?php
namespace verbb\workflow;

use verbb\workflow\base\PluginTrait;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Settings;
use verbb\workflow\variables\WorkflowVariable;
use verbb\workflow\widgets\Submissions as SubmissionsWidget;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\SystemMessages;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\web\User;

class Workflow extends Plugin
{
    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerEmailMessages();
        $this->_registerWidgets();
        $this->_registerVariables();
        $this->_registerElementTypes();

        Craft::$app->view->hook('cp.entries.edit.details', [$this->getService(), 'renderEntrySidebar']);
    }

    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('workflow/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'workflow/drafts' => 'workflow/base/drafts',
                'workflow/settings' => 'workflow/base/settings',
            ]);
        });
    }

    private function _registerEmailMessages()
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
            $event->messages = array_merge($event->messages, [
                [
                    'key' => 'workflow_publisher_notification',
                    'heading' => Craft::t('workflow', 'workflow_publisher_notification_heading'),
                    'subject' => Craft::t('workflow', 'workflow_publisher_notification_subject'),
                    'body' => Craft::t('workflow', 'workflow_publisher_notification_body'),
                ], [
                    'key' => 'workflow_editor_notification',
                    'heading' => Craft::t('workflow', 'workflow_editor_notification_heading'),
                    'subject' => Craft::t('workflow', 'workflow_editor_notification_subject'),
                    'body' => Craft::t('workflow', 'workflow_editor_notification_body'),
                ]
            ]);
        });
    }

    private function _registerWidgets()
    {
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = SubmissionsWidget::class;
        });
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('workflow', WorkflowVariable::class);
        });
    }

    private function _registerElementTypes()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Submission::class;
        });
    }
}
