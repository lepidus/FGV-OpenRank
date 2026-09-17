<?php

class Manage
{
    public $plugin;

    public function __construct(&$plugin)
    {
        $this->plugin = &$plugin;
    }

    public function execute($args, $request): JSONMessage
    {
        $user = $request->getUser();
        import('classes.notification.NotificationManager');
        $notificationManager = new NotificationManager();

        switch ($request->getUserVar('verb')) {
            case 'configurationGuide':
                import('plugins.generic.rankingPlugin.classes.settings.ConfigurationGuide');
                $configurationGuide = new ConfigurationGuide($this->plugin);
                return $configurationGuide->execute($request);
            case 'settings':
                $context = $request->getContext();
                import('plugins.generic.rankingPlugin.classes.settings.RankingPluginSettingsForm');
                $form = new RankingPluginSettingsForm($this->plugin, $context->getId());
                $form->initData();
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        $notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS);
                        return new JSONMessage(true);
                    }
                }
                return new JSONMessage(true, $form->fetch($request));
            default:
                return $this->plugin->parentManage($args, $request);
        }
    }
}
