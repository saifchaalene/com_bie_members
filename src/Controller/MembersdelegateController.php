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
 * Membersdelegate controller class.
 *
 * @since  1.0.0
 */
class MembersdelegateController extends FormController
{

	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list =  'membersdelegates';
		parent::__construct();
	}
        
        
        
public function denounce() {
                $input = Factory::getApplication()->input;

                
                $data = $input->post->get('jform',array(), 'array');
                if (!$data['id'] || !$data['end_date']) {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_END_DATE',$data['first_name'].' '.$data['last_name']), 'error');
                    $this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegate&layout=denounce&id='.$data['id'], false));                    
                } else {
                    
                    $model = \Joomla\CMS\MVC\Model\BaseDatabaseModel::getInstance('Membersdelegate', 'Combiemembers\Component\Bie_members\Administrator\Model\\', ['ignore_request' => true]);
                    $return = $model->denounce($data);                    
                    $this->setRedirect(Route::_('index.php?option=com_bie_members&view=Membersdelegates', false));
                    
                }                               
        
    }        
    
public function reannounce() {
                $input = Factory::getApplication()->input;

                
                $data = $input->post->get('jform',array(), 'array');
                if (!$data['id'] || !$data['start_date']) {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_EDIT_DELEGATE_START_DATE',$data['first_name'].' '.$data['last_name']), 'error');
                    $this->setRedirect(Route::_('index.php?option=com_bie_members&view=delegate&layout=reannounce&id='.$data['id'], false));                    
                } else {
                    
                    $model = \Joomla\CMS\MVC\Model\BaseDatabaseModel::getInstance('Membersdelegate', 'Combiemembers\Component\Bie_members\Administrator\Model\\', ['ignore_request' => true]);
                    $return = $model->reannounce($data);                    
                    $this->setRedirect(Route::_('index.php?option=com_bie_members&view=Membersdelegates', false));
                    
                }                               
        
    }        


}
