<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Combiemembers\Component\Bie_members\Administrator\Extension\Bie_membersComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;


/**
 * The Bie_members service provider.
 *
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function register(Container $container)
	{

		$container->registerServiceProvider(new CategoryFactory('\\Combiemembers\\Component\\Bie_members'));
		$container->registerServiceProvider(new MVCFactory('\\Combiemembers\\Component\\Bie_members'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Combiemembers\\Component\\Bie_members'));
		$container->registerServiceProvider(new RouterFactory('\\Combiemembers\\Component\\Bie_members'));

		$container->set(
			ComponentInterface::class,
			function (Container $container)
			{
				$component = new Bie_membersComponent($container->get(ComponentDispatcherFactoryInterface::class));

				$component->setRegistry($container->get(Registry::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
