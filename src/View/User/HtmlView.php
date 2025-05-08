<?php

namespace Combiemembers\Component\Bie_members\Administrator\View\User;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Response\JsonResponse;

class HtmlView extends BaseHtmlView
{
    public function display($tpl = null)
    {
        $user = Factory::getApplication()->getIdentity();
    
        if ($user->guest) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Not logged in',
            ]);
            exit;
        }
    
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'logged_in' => true,
        ]);
        exit;
    }
    
}
