<?php
namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session; 

class UserController extends BaseController
{
    public function getCurrent(): void
    {
        $app = Factory::getApplication();
    
        $app->mimeType = 'application/json';
        $app->setHeader('Content-Type', $app->mimeType . '; charset=' . $app->charSet);
        $app->sendHeaders();
    
        if (!Session::checkToken('get')) {
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
        }
    
        $user = $app->getIdentity();
        if ($user->guest) {
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $app->close();
        }
    
        echo new JsonResponse([
            'id'        => $user->id,
            'name'      => $user->name,
            'username'  => $user->username,
            'email'     => $user->email,
            'logged_in' => true,
        ]);
    
        $app->close();
    }
}