<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Extension;

defined('JPATH_PLATFORM') or die;

use Combiemembers\Component\Bie_members\Administrator\Service\Html\BIE_MEMBERS;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Tag\TagServiceTrait;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\ApiRouter;
use Combiemembers\Component\Bie_members\Administrator\Controller\AngularController;  // <-- Make sure this is imported

/**
 * Component class for Bie_members
 *
 * @since  1.0.0
 */
class Bie_membersComponent extends MVCComponent implements RouterServiceInterface, BootableExtensionInterface, CategoryServiceInterface
{
    use AssociationServiceTrait;
    use RouterServiceTrait;
    use HTMLRegistryAwareTrait;
    use CategoryServiceTrait, TagServiceTrait {
        CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
        CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
    }

    /** @inheritdoc  */
    public function boot(ContainerInterface $container)
    {
        $db = $container->get('DatabaseDriver');
        $this->getRegistry()->register('bie_members', new BIE_MEMBERS($db));

        $app = Factory::getApplication();

        if ($app->isClient('api')) 
        {
            /** @var ApiRouter $router */
            $router = $app->getRouter();

            // Updated to use the new controller class name
			$router->get(
				'bie_members/angular/contact',
				[
					'controller' => AngularController::class,  // <-- updated here
					'method'     => 'display',
				]
			);
			
        }
    }

    /**
     * Returns the table for the count items functions for the given section.
     *
     * @param   string    The section
     *
     * @return  string|null
     *
     * @since   4.0.0
     */
    protected function getTableNameForSection(string $section = null)            
    {
    }

    /**
     * Adds Count Items for Category Manager.
     *
     * @param   \stdClass[]  $items    The category objects
     * @param   string       $section  The section
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function countItems(array $items, string $section)
    {
    }
}
