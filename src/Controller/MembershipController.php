<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis
 * @copyright  2025
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/**
 * Membership controller class.
 *
 * @since  1.0.0
 */
class MembershipController extends FormController
{
	/**
	 * The default view for this controller.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $view_list = 'memberships';

	/**
	 * Override save method to redirect after saving
	 *
	 * @param   string|null  $key     The name of the primary key of the URL variable.
	 * @param   string|null  $urlVar  The name of the URL variable if different from the primary key (null = use $key).
	 *
	 * @return  bool  True if save was successful, false otherwise.
	 */
	public function save($key = null, $urlVar = null): bool
	{
		$return = parent::save($key, $urlVar);

		$this->setRedirect(
			Route::_('index.php?option=com_bie_members&view=membership&layout=edit', false)
		);

		return $return;
	}

	/**
	 * Override cancel method to redirect after cancel
	 *
	 * @param   string|null  $key     The name of the primary key of the URL variable.
	 * @param   string|null  $urlVar  The name of the URL variable if different from the primary key (null = use $key).
	 *
	 * @return  bool  True if cancel was successful, false otherwise.
	 */
	public function cancel($key = null, $urlVar = null): bool
	{
		$return = parent::cancel($key, $urlVar);

		$this->setRedirect(
			Route::_('index.php?option=com_bie_members&view=delegates', false)
		);

		return $return;
	}
}
