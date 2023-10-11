<?php
/**
 * @version		1.0.8
 * @package		CGChangeLog content plugin
 * @author		ConseilGouz
 * @copyright	Copyright (C) 2023 ConseilGouz. All rights reserved.
 * @license		GNU/GPL v2; see LICENSE.php
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Http\HttpFactory;
use ConseilGouz\Plugin\Content\CGChangelog\Extension\CGChangelog;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.2.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
				$displatcher = $container->get(DispatcherInterface::class);
                $plugin = new CGChangelog(
                    $displatcher,
                    (array) PluginHelper::getPlugin('content', 'cgchangelog')
                );
                $plugin->setApplication(Factory::getApplication());
                return $plugin;
            }
        );
    }
};
