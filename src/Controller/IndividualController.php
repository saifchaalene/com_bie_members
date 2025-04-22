<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/**
 * Individual controller class.
 *
 * @since  1.6
 */
class IndividualController extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws \Exception
	 */
	public function __construct($config = [])
	{
		$this->view_list = 'individuals';
		parent::__construct($config);
	}

	/**
	 * Override save method to add custom redirection.
	 */
	public function save($key = null, $urlVar = null)
	{
		$return = parent::save($key, $urlVar);
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=individual&layout=edit', false));
		return $return;
	}

	/**
	 * Override cancel method to add custom redirection.
	 */
	public function cancel($key = null, $urlVar = null)
	{
		$return = parent::cancel($key, $urlVar);
		$this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegates', false));
		return $return;
	}
}
