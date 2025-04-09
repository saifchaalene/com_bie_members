<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Combiemembers\Component\Bie_members\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date as JDate;


class Bie_membersUtils
{
    public static function getContactId($joomlaUserId = null)
    {
        // If no user ID provided, use current logged-in user
        if (!$joomlaUserId) {
            $joomlaUser = Factory::getApplication()->getIdentity();
            $joomlaUserId = $joomlaUser->id ?? 0;
        }
        $joomlaUserId = (int) $joomlaUserId;
        if ($joomlaUserId === 0) {
            return null;
        }

        // Initialize CiviCRM if not already done (ensure CiviCRM API is available)
        if (!defined('CIVICRM_SETTINGS_PATH')) {
            // Adjust the path if needed for Joomla 5 directory structure
            define('CIVICRM_SETTINGS_PATH', JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm.settings.php');
        }
        if (defined('CIVICRM_SETTINGS_PATH') && !defined('CIVICRM_DSN')) {
            include_once CIVICRM_SETTINGS_PATH;
            // Bootstrap CiviCRM
            if (class_exists('CRM_Core_Config')) {
                \CRM_Core_Config::singleton();
            }
        }

        // Use CiviCRM API (UFMatch) to find contact by user ID
        try {
            $result = civicrm_api3('UFMatch', 'get', [
                'uf_id'     => $joomlaUserId,
                'uf_name'   => $joomlaUserId,  // sometimes UFMatch can be looked up by username or ID
                'domain_id' => CRM_Core_Config::domainID(),
            ]);
        } catch (Exception $e) {
            // If API is not available or call fails, fall back to direct database query
            $db = Factory::getContainer()->get('DatabaseDriver');
            $prefix = $db->getPrefix();  // Joomla database table prefix
            $query = $db->getQuery(true)
                        ->select($db->quoteName('contact_id'))
                        ->from($db->quoteName("{$prefix}civicrm_uf_match"))
                        ->where($db->quoteName('uf_id') . ' = ' . $joomlaUserId);
            try {
                $db->setQuery($query);
                return (int) $db->loadResult();
            } catch (RuntimeException $dbError) {
                Factory::getApplication()->enqueueMessage('Error fetching contact ID: ' . $dbError->getMessage(), 'error');
                return null;
            }
        }

        if (!empty($result['values'])) {
            // Return the first matching contact_id
            $matches = $result['values'];
            $match = reset($matches);
            return isset($match['contact_id']) ? (int) $match['contact_id'] : null;
        }

        return null;
    }
    public static function isMemberActive($joomlaUserId = null)
    {
        $contactId = self::getContactId($joomlaUserId);
        if (!$contactId) {
            return false;
        }

        // Ensure CiviCRM is initialized (if not already from getContactId)
        if (!class_exists('CRM_Core_DAO')) {
            // CiviCRM not bootstrapped yet, attempt to include necessary core class
            if (defined('CIVICRM_SETTINGS_PATH')) {
                include_once CIVICRM_SETTINGS_PATH;
            }
            if (class_exists('CRM_Core_Config')) {
                \CRM_Core_Config::singleton();
            }
        }

        // Fetch active memberships for the contact via CiviCRM API
        try {
            $membership = civicrm_api3('Membership', 'get', [
                'contact_id'   => $contactId,
                'active_only'  => true,
                'options'      => ['limit' => 1]  // we only need to know if at least one exists
            ]);
        } catch (Exception $e) {
            // API failed – try direct query on civicrm_membership table for status
            $db = Factory::getContainer()->get('DatabaseDriver');
            $prefix = $db->getPrefix();
            $query = $db->getQuery(true)
                        ->select('COUNT(*)')
                        ->from($db->quoteName("{$prefix}civicrm_membership", 'm'))
                        ->join('INNER', $db->quoteName("{$prefix}civicrm_membership_status", 's') . 
                                           ' ON m.status_id = s.id')
                        ->where('m.contact_id = ' . (int) $contactId)
                        ->where($db->quoteName('s.is_current_member') . ' = 1');
            try {
                $db->setQuery($query);
                $count = (int) $db->loadResult();
                return ($count > 0);
            } catch (RuntimeException $dbError) {
                Factory::getApplication()->enqueueMessage('Error checking membership status: ' . $dbError->getMessage(), 'error');
                return false;
            }
        }

        return (!empty($membership['count']) && $membership['count'] > 0);
    }
    public static function getMembershipEndDate($joomlaUserId = null)
    {
        $contactId = self::getContactId($joomlaUserId);
        if (!$contactId) {
            return null;
        }

        // Use CiviCRM Membership API to get membership info
        try {
            $membership = civicrm_api3('Membership', 'getsingle', [
                'contact_id'  => $contactId,
                'active_only' => true,
                'return'      => ["end_date"],
                'options'     => ['sort' => "end_date DESC"]
            ]);
        } catch (Exception $e) {
            // If API fails or no active membership, return null
            return null;
        }

        return $membership['end_date'] ?? null;
    }
    public static function redirectIfNoMembership($redirectItemId)
    {
        // If the current user is not an active member, redirect to given page
        if (!self::isMemberActive()) {
            $app = Factory::getApplication();
            $url = is_numeric($redirectItemId)
                ? Route::_("index.php?Itemid=" . (int) $redirectItemId)
                : Route::_($redirectItemId);
            $app->redirect($url);
        }
    }

    /**
     * Example function: Redirect non-members to a specific page.
     * (Uses Joomla routing and URI methods updated for Joomla 5.)
     *
     * @param string $redirectItemId  ItemID or URL to redirect to.
     */


	public static function isAdmin()
	{
		//group 7 super user
		//group 8 BIE Admins
        $user = Factory::getApplication()->getIdentity();
		$intersect = array_intersect(array(7, 8, 12), $user->groups);

		return (count($intersect) > 0) ? true : false;

	}

	public static function allowEditDelegates()
	{
		//group 7 super user
		//group 8 BIE Admins
        $user = Factory::getApplication()->getIdentity();
		$intersect = array_intersect(array(7, 8, 12,11), $user->groups);

		return (count($intersect) > 0) ? true : false;

	}


	/**
	 * @param $contact_id
	 *
	 * @return mixed
	 * Int:CiviCRM USer ID or False
	 *
	 * @since version
	 */
	public static function getCiviCrmContactByID($contact_id){
        
        $api = new civicrm_api3(array(
              // Specify location of "civicrm.settings.php".
              'conf_path' => JPATH_BASE . '/components/com_civicrm',
        ));

        $api = new civicrm_api3(array(
              // Specify location of "civicrm.settings.php".
              'conf_path' => JPATH_BASE . '/components/com_civicrm',
            ));
        
        
              $params = array(
                        'contact_id' => $contact_id,
                  );

              if ($api->Contact->get($params) )             
                return $api->lastResult->values[0];
              else 
              
              return false;
    }
    
    
    public static function acymailing_generateKey($length){
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randstring = '';
	$max = strlen($characters) - 1;
	for($i = 0; $i < $length; $i++){
		$randstring .= $characters[mt_rand(0, $max)];
	}
	return $randstring;
    }         
     
    
    public static function initDelegatesMailingList() {
        $now = new JDate();
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = "DELETE l "
                . "FROM biecrm_acym_user_has_list AS l "
                . "JOIN biecrm_acym_user AS sub ON l.user_id = sub.id "
                . "WHERE l.list_id = 3";
        $db->setQuery($query);
        $db->execute();
        
        
        $query = "SELECT * FROM `civicrm_delegates_current` WHERE `email` IS NOT NULL";
        $db->setQuery($query);
        $results = $db->loadObjectList();

        
        foreach ($results as $row) {
            $row->contact_id = $row->id;
	        $acymailid = Bie_membersUtils::contactInAcymail($row);
            if ($acymailid == 0) {
                $acymailid = Bie_membersUtils::insertUserNewsletter($row);
            } else {
	            Bie_membersUtils::updateContactInAcymail($row,$acymailid);
            }
            $query = $db->getQuery(true);            
            $columns = array('user_id', 'list_id', 'subscription_date', 'status');
            $values = array($db->quote($acymailid), $db->quote(3), $db->quote($now->toSql()),$db->quote(1));
            $query->insert($db->quoteName('#__acym_user_has_list'))
                  ->columns($db->quoteName($columns))
                  ->values(implode(',', $values));
            $db->setQuery($query);
            $db->execute();
        }
        
        return true;
     }

    
    
    
     public static function initDelegatesMailingListBOWS() {
		$now = new JDate();
        $db = Factory::getContainer()->get('DatabaseDriver');
		$query = "DELETE l "
                . "FROM a9ys0_acym_user_has_list AS l "
                . "JOIN a9ys0_acym_user AS sub ON l.user_id = sub.id "
                . "WHERE l.list_id = 1";
        $db->setQuery($query);
        $db->execute();

        $query = "SELECT * FROM `civicrm_delegates_current` WHERE `email` IS NOT NULL";
        $db->setQuery($query);
        $results = $db->loadObjectList();
        
        foreach ($results as $row) {
	        $row->contact_id = $row->id;
	        $acymailid = Bie_membersUtils::contactInAcymail($row,'a9ys0_acym_user');
	        if ($acymailid == 0) {
		        $acymailid = Bie_membersUtils::insertUserNewsletter($row,'a9ys0_acym_user');
	        } else {
		        Bie_membersUtils::updateContactInAcymail($row,$acymailid,'a9ys0_acym_user');
	        }
	        $query = $db->getQuery(true);
	        $columns = array('user_id', 'list_id', 'subscription_date', 'status');
	        $values = array($db->quote($acymailid), $db->quote(1), $db->quote($now->toSql()),$db->quote(1));
	        $query->insert($db->quoteName('a9ys0_acym_user_has_list'))
		        ->columns($db->quoteName($columns))
		        ->values(implode(',', $values));
	        $db->setQuery($query);
	        $db->execute();
        }
	     return true;
     }

	public static function syncLocalCopyDelegatesBOWSUsers() {

		$items = [];

		$repsone = self::callBOWSRestApi('bowsdocs/users',"GET");
		$items = $repsone->data ?? array();

		if(count($items) > 0) {
            $db = Factory::getContainer()->get('DatabaseDriver');
			self::truncateTable('civicrm_bows_delegates_users_ids',$db);

			foreach ($items as $item) {
				$attributes = $item->attributes;
				$query = $db->getQuery(true);
				$columns = array('bows_user_id', 'contact_id','username','email','name');
				$values = array($db->quote($attributes->id), $db->quote($attributes->crm_id),$db->quote($attributes->username),$db->quote($attributes->email),$db->quote($attributes->name));

				$query->insert($db->quoteName('civicrm_bows_delegates_users_ids'))
					->columns($db->quoteName($columns))
					->values(implode(',', $values));
				$db->setQuery($query);
				try {
					$db->execute();
				} catch (Exception $e) {
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
					Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'warning');
					return false;
				}


			}
		}
	}

	protected static function callBOWSRestApi(string $endpoint,string $request = "GET") {
		$curl = curl_init();

		$posts            = array();
		$posts['token']   = 'c2hhMjU2OjQyOjJlNTVkZWY3OTVkYTczMzcwYTAxZDMyOTkyNzFkMGMxZjgyNWMxNTM1ZDg0MzYwNTg4NDczNTBiZTU2YWE5NWY';

		curl_setopt_array($curl, array(
			CURLOPT_URL            => "https://bows.bie-paris.org/api/index.php/v1/".$endpoint,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => $request,
			CURLOPT_HTTPHEADER     => array(
				"Authorization: Bearer ".$posts['token'],
			),
		));

		$results = curl_exec($curl);
		$err      = curl_error($curl);

		if ($err)
		{
			Factory::getApplication()->enqueueMessage('Curl Error: ' . $err, 'notify');
		}
		else
		{
			$repsone = json_decode($results);
		}
		curl_close($curl);
		return $repsone;
	}

	protected static function truncateTable($table,$db) {
		$query = "TRUNCATE TABLE ".$table;
		$db->setQuery($query);
		$db->execute();
	}

     public static function isDelegateCurrent($id) {

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = "SELECT count(*) FROM `civicrm_delegates_current` where id = ".$db->quote($id);
         $db->setQuery($query);
         $count = $db->loadResult(); 
         return ($count > 0) ? true : false;
            
     }
     
     
     public static function getDelegateCurrentMid($id) {

$db = Factory::getContainer()->get('DatabaseDriver');
         $query = "SELECT mid FROM `civicrm_delegates_current` where id = ".$db->quote($id);
         $db->setQuery($query);
         $mid = $db->loadResult(); 
         return ($mid > 0) ? $mid : 0;
            
     }

     public static function isDelegateFormer($id) {

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = "SELECT count(*) FROM `civicrm_delegates_former` where id = ".$db->quote($id);
         $db->setQuery($query);
         $count = $db->loadResult();
         return ($count > 0) ? true : false;
            
     }
     
     
     
     
    
    public static function getBOWSUser($contact_id) {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
	$query
		->select('*')
                ->from($db->quoteName('a9ys0_users','u'))
                ->join('INNER', $db->quoteName('civicrm_bie_delegates_users_ids', 'c') . ' ON (' . $db->quoteName('c.user_id') . ' = ' . $db->quoteName('u.id') . ')')
		->where($db->quoteName('c.contact_id') . ' = '.$db->quote($contact_id));
	$db->setQuery($query);
	return $db->loadObject();        
        
    }
	public static function getNewBOWSUser($contact_id) {
        $db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query
			->select('*')
	                ->from($db->quoteName('civicrm_bows_delegates_users_ids','u'))
			->where($db->quoteName('u.contact_id') . ' = '.$db->quote($contact_id));
		$db->setQuery($query);
		//Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');
		return $db->loadObject();
    }

	public static function getConfig($file, $type = 'PHP', $namespace = '') {
		if (is_file($file))
		{
			include_once $file;
		}

		// Create the registry with a default namespace of config
		$registry = new Registry;

		// Sanitize the namespace.
		$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));

		// Build the config name.
		$name = 'JConfig' . $namespace;

		// Handle the PHP configuration type.
		if ($type == 'PHP' && class_exists($name))
		{
			// Create the JConfig object
			$config = new $name;

			// Load the configuration values into the registry
			$registry->loadObject($config);
		}

		return $registry;
	}

	public static function deleteBOWSUser($contact_id) {
		$config = self::getConfig(JPATH_ROOT.'/bie.config.php','PHP','BOWS');
//		$api_key    = $config->get('bows_sandboxed_api_key');
//		$api_url    = $config->get('bows_sandboxed_api_url');
		$api_key    = $config->get('bows_api_key');
		$api_url    = $config->get('bows_api_url');



        $db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db
			->getQuery(true)
			->select('bows_user_id')
			->from($db->quoteName('civicrm_bows_delegates_users_ids'))
			->where($db->quoteName('contact_id') . " = " . $db->quote($contact_id));
		$db->setQuery($query);
		$userID = $db->loadResult();

		echo $contact_id." - ".$userID;
		if (intval($userID) > 0 ) {
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $api_url."/".$userID,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "DELETE",
				CURLOPT_HTTPHEADER => array(
					"X-Joomla-Token:".$api_key
				),
			));

			$results = curl_exec($curl);
			$err = curl_error($curl);

			if ($err) {
				echo 'Curl Error: ' . $err;
			} else {
				$response = json_decode($results);
			}
			curl_close($curl);

			$query = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('contact_id') . ' = '.$contact_id,
			);
			$query->delete($db->quoteName('civicrm_bows_delegates_users_ids'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->execute();

			//print_r($response);
		}


	}

    
    
    public static function logVotingRightChanges($pks,$vote) {

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $user = Factory::getApplication()->getIdentity();
        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));  
            $dt =  $date->format('Y\-m\-d\ H:i:s');

            if (count($pks) > 0 ) {                
                foreach ($pks as $contact_id) {
                    $columns = array('userid', 'contact_id', 'right_to_vote', 'entry_datetime');
                    $values = array($user->id, $contact_id, $vote,$db->quote($dt));
                    $query
                        ->insert($db->quoteName('civicrm_mstate_vote_log'))
                        ->columns($db->quoteName($columns))
                        ->values(implode(',', $values));

                    // Set the query using our newly populated query object and execute it.
                    $db->setQuery($query);
                    $db->execute();                    
                    
                    
                }
                
            }

        
    }
    
    
    
    public static function getCiviCRMUser() {
        $user = Factory::getApplication()->getIdentity();
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
	$query
		->select('*')
                ->from($db->quoteName('civicrm_users'))
		->where($db->quoteName('uf_id') . ' = '. $db->quote($db->escape($user->id)));
	$db->setQuery($query);
	return $db->loadObject();        
        
    }

        public static function num_format($number,$decimals = 0) {
        $lang = Factory::getLanguage();
        $tag = $lang->getTag();
        $l = explode("-", $tag);        
        
        if ($l[0] == 'fr') {
            return number_format($number, $decimals, ',', ' ');
        } else {
            return number_format($number, $decimals, ',', ' ');
        }
    }

    
 
    
    
    
    public static function dateformat($date){
         $d = explode("-", $date);
         if (($date != '0000-00-00') && count($d) == 3) {
             return $d[2]."/".$d[1]."/".$d[0];
         }
         
         return "";
     }    
    
    public static function isodateformat($date){
         $d = explode("/", $date);
         if (($date != '00/00/0000') && count($d) == 3) {
             return $d[2]."-".$d[1]."-".$d[0];
         }
         
         return "";
     }    
    
     
   public static function setUserEnabledField($block,$contact_id) {
    $db = Factory::getContainer()->get('DatabaseDriver');
	$query = $db->getQuery(true);
        // Fields to update.
        $fields = array(
            $db->quoteName('u.block') . ' = ' . $db->quote($block)
        );

        $conditions = array(
            $db->quoteName('c.contact_id') . ' = '.$db->quote($contact_id), 
        );


        $query->update($db->quoteName('a9ys0_users','u'))
              ->join('INNER', $db->quoteName('civicrm_bie_delegates_users_ids', 'c') . ' ON (' . $db->quoteName('c.user_id') . ' = ' . $db->quoteName('u.id') . ')')
              ->set($fields)
              ->where($conditions);

        $db->setQuery($query);
        $db->query();
       
        return true;       
   }  
     
     
   public static function createJoomlaUserByID($contact_id) {
       
    $db = Factory::getContainer()->get('DatabaseDriver');
    $query = $db->getQuery(true);
    $query->select("*")
          ->from('civicrm_contact') 
          ->where('id = '.$db->quote($contact_id));
    $db->setQuery($query);
    $data = $db->loadAssoc();   
    
    $org = Bie_membersUtils::getExtraFields(JArrayHelper::getValue($data, 'employer_id',0));
    
    return Bie_membersUtils::createJoomlaUser($data,$contact_id,$org);
       
   }
   
   
   
   public static function random_string($type = 'alnum', $len = 8)
   {
	   switch ($type)
	   {
		   case 'basic':
			   return mt_rand();
		   case 'alnum':
		   case 'numeric':
		   case 'nozero':
		   case 'alpha':
			   switch ($type)
			   {
				   case 'alpha':
					   $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					   break;
				   case 'alnum':
					   $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					   break;
				   case 'numeric':
					   $pool = '0123456789';
					   break;
				   case 'nozero':
					   $pool = '123456789';
					   break;
			   }
			   return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
		   case 'md5':
			   return md5(uniqid(mt_rand()));
		   case 'sha1':
			   return sha1(uniqid(mt_rand(), TRUE));
	   }
   }

	public static function getMailForBOWS($mail,$bowsID = 0) {

        $db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')
			->from($db->quoteName('a9ys0_users'))
			->where($db->quoteName('email') . " = " . $db->quote($mail));
		if($bowsID > 0) {
			$query->where($db->quoteName('id') . " != " . $db->quote($bowsID));
		}

		$db->setQuery($query);
		//Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');
		$result = $db->loadResult();
		if($result > 0) {
			Factory::getApplication()->enqueueMessage("Mail ".$mail." is already in use on BOWS. The User has been saved but Please change his mail to something unique!!", 'warning');
			return "bie_".uniqid()."_".$mail;
		} else {
			return $mail;
		}
	}
   
    public static function createJoomlaUser($delegate,$civicrm_id,$org) {
  
    

        
  return true;

    
    
}


    public static function usernameExists($username)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
            ->from($db->quoteName('a9ys0_users'))
            ->where($db->quoteName('username') . " = " . $db->quote($username));

        $db->setQuery($query);
        //Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');
        $result = $db->loadResult();
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }
public static function parseUsername($lastname) {
    $lastname = strtolower($lastname);
    $lastname = self::remove_accents($lastname);
    $t = str_replace(",","",$lastname);
    $t = str_replace("-"," ",$t);
    $t = str_replace("'"," ",$t);
    $t = str_replace("."," ",$t);
    
    $k = explode(" ",$t);
    if (strlen($k[0]) < 4 && count($k) > 1) {
        if (strlen($k[1]) > 3) {
            return $k[1];
        } else {
            return $k[0].$k[1];
        }
    } else {
        return $k[0];
    }
}


public static function getExtraFields($entity_id) {
    
    $db = Factory::getContainer()->get('DatabaseDriver');
    $query = $db->getQuery(true);
    $query->select("*")
          ->from('civicrm_value_organisaztion_name_en_1') 
          ->where('entity_id = '.$db->quote($entity_id));
    $db->setQuery($query);
    return $db->loadObject();
    
    
    
}




public static function updateBOWSDelegate($delegate, $createNewPass = false) {

	// $user = new stdClass();
	// $user->id = $delegate['bows_id'];
	// $user->email = self::getMailForBOWS($delegate['email'],$delegate['bows_id']);
	// $user->name = $delegate['name'];
	// $user->block = JArrayHelper::getValue($delegate, 'block',0);

	// if ($createNewPass)
	// {
	// 	$password               = self::random_string('alnum', 8);
	// 	$delegate['o_password'] = $password;
	// 	$salt                   = JUserHelper::genRandomPassword(32);
	// 	$crypted                = JUserHelper::getCryptedPassword($password, $salt);
	// 	$user->password         = $crypted . ':' . $salt;
	// 	$user->requireReset     = 0;
	// 	$user->activation       = '';
	// }

	// $result = Factory::getDbo()->updateObject('a9ys0_users', $user, 'id');

	// if (!$result) {
	// 	//thronew Exception("Could not save user. Error: " . $user->getError());
	// 	//$errormsgs[] = "Error Saving: ".implode("::", $delegate)." :: Description: ".$user->getError();
	// 	return "Error Saving: ".implode("::", $delegate)." :: Description: ".$user->getError();
	// }

	// if ($createNewPass) {
	// 	self::sendNewUSerMail($delegate);
	// }
	return true;
}


public static function sendNewUSerMail ($delegate) {
    
//     $config = Factory::getConfig();
//     $mailer = Factory::getMailer();
//     $application = Factory::getApplication();
//     $sender = array( 
//         $config->get( 'mailfrom' ),
//         $config->get( 'fromname' ) 
//     );
 
//     $mailer->setSender($sender);   
//     $recipient = JArrayHelper::getValue($delegate, 'email');
    
//     $mailer->addRecipient($recipient);
// //	$mailer->addRecipient("tasos@bie-paris.org");
//     $mailer->addBcc("bows@bie-paris.org");
    
    
//     $mailer->setSubject('BOWS :: User Account');
    
//     $body  = "<p>Dear Delegate ".JArrayHelper::getValue($delegate, 'name').",</p>
// 			<p>From now on you may connect to BOWS. In order to connect to BOWS, click on the following link: <a href=\"https://bows.bie-paris.org/\">https://bows.bie-paris.org/</a></p>
// 			<br>In the Login Page, please use the following account:</br>
// 			<strong>Username:</strong> ".JArrayHelper::getValue($delegate, 'username')."</br>
// 			<strong>Password:</strong> ".JArrayHelper::getValue($delegate, 'o_password')."
// 			</p>
// 			<p><strong>Advices for successful connection on BOWS</strong>:</p>
// 			<ul>
// 				<li>The username as well as the password are case sensitive. Meaning, you should use capital letter where there is capital and small letter where are small.</li>
// 				<li>Avoid spaces in the front and the back of each word</li>
// 				<li>Try to copy-paste your username and/or password by:
// 					<ul>
// 						<li>double-clicking on each word</li>
// 						<li>then right click with your mouse</li>
// 						<li>select copy</li>
// 						<li>Click inside the box “username” in the login page of BOWS</li>
// 						<li>Right click and select paste</li>
// 					</ul>
// 				</li>
// 			</ul>
// 			<p><strong>After login on BOWS</strong></p>
// 			<p>The password that has been sent by BOWS it’s been generated by BOWS and it’s temporary. We advise you to change it by clicking on the menu Profile -&gt; Edit Profile</p>
// 			<p>The new password must include</p>
// 			<ul>
// 				<li>At least 8 Characters</li>
// 				<li>At least one capital letter</li>
// 				<li>At least one small letter</li>
// 				<li>At least one number</li>
// 				<li>No spaces</li>
// 			</ul>
// 			<p><strong>In case of loss of your password</strong></p>
// 			<p>please click on the link: “forgot your password” which exists in the login page. You will need your e-mail and your username in order to reset your password</p>
// 			<p><strong>Contacting BOWS Administrator</strong></p>
// 			<p>In case you would like to contact BOWS Administrator, you may do so by sending an e-mail to <a href=\"mailto:bows@bie-paris.org\"></a><a href=\"mailto:bows@bie-paris.org\">bows@bie-paris.org</a></p>                 
// 			  <p>========================================================</p>
// 			<p>Monsieur le délégué, Madame le délégué</p>
// 			<p>Le lien afin de vous connecter sur BOWS est : <a href=\"https://bows.bie-paris.org\">https://bows.bie-paris.org</a></p>
// 			<p>Il faut utiliser le compte suivant le compte suivant :<br />
// 			<strong>Votre identifiant</strong> : ".JArrayHelper::getValue($delegate, 'username')."<br />
// 			<strong>Votre mot de passe</strong> : ".JArrayHelper::getValue($delegate, 'o_password')."<br /> <br />
// 			Dans le cas suivant, je me permets de vous rappeler qu’il est essentiel que:</p>
// 			<ul>
// 				<li>Les caractères en Majuscules (par exemple pour votre identifiant: DRC) soient bien TOUS en Majuscules.</li>
// 				<li>Les caractères en Minuscules (par exemple pour votre identifiant: manga) soient bien TOUS en Minuscules.</li>
// 				<li>Aucun espace: au début, entre les caractères, à la fin (exemple ci-dessous)</li>
// 			</ul>
// 			<p><strong>Deux possibilités s’offrent à vous</strong> :</p>
// 			<ul>
// 				<li>Copier-coller votre identifiant et votre mot de passe. Si vous choisissez cette option, il faut :
// 					<ul>
// 						<li>Cliquer deux fois sur votre identifiant/mot de passe</li>
// 						<li>Utiliser le clic droit de votre souris et cliquer sur copier</li>
// 						<li>Sur la page d’accueil de BOWS : Utiliser le clic droit de votre souris et Coller l’identifiant/mot de passe</li>
// 					</ul>
// 				</li>
// 			</ul>
// 			<p>Réécrire votre identifiant et mot de passe dans les cases correspondantes en veillant à bien respecter les majuscules, minuscules et sans mettre d’espace.</p>
// 			<p><strong>Suite votre première connexion sur BOWS,</strong> je vous invite à changer ce mot de passe et choisir un autre que vous convient mieux en cliquant le menu Profil -&gt; Modifier le Profil.</p>
// 			<p>Le nouveau mot de passe doit inclure :</p>
// 			<ul>
// 				<li>Au moins 8 caractères</li>
// 				<li>Au moins une majuscule</li>
// 				<li>Au moins une minuscule</li>
// 				<li>Au moins un chiffre</li>
// 				<li>Pas des espaces</li>
// 			</ul>
// 			<p><strong>Contacter l'administrateur BOWS</strong></p>
// 			<p>Si vous souhaitez contacter l'administrateur BOWS, vous pouvez le faire en envoyant un e-mail à <a href=\"mailto:bows@bie-paris.org\">bows@bie-paris.org</a></p>
//   ";
//     $mailer->isHTML(true);
//     $mailer->Encoding = 'base64';
//     $mailer->setBody($body);
//     $send = $mailer->Send();
    
//       if ($send !== true) {
//          $application->enqueueMessage("Error Sending mail: ".JArrayHelper::getValue($delegate, 'email')." :: Description: ", 'error');
//           return FALSE;
//       }
    
     return true;
    
    
}



public static function remove_accents($string) {
	if ( !preg_match('/[\x80-\xff]/', $string) )
		return $string;

	if (self::seems_utf8($string)) {
		$chars = array(
		// Decompositions for Latin-1 Supplement
		chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
		chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
		chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
		chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
		chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
		chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
		chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
		chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
		chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
		chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
		chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
		chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
		chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
		chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
		chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
		chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
		chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
		chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
		chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
		chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
		chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
		chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
		chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
		chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
		chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
		chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
		chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
		chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
		chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
		chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
		chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
		chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',
		// Decompositions for Latin Extended-A
		chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
		chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
		chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
		chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
		chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
		chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
		chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
		chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
		chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
		chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
		chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
		chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
		chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
		chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
		chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
		chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
		chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
		chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
		chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
		chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
		chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
		chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
		chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
		chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
		chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
		chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
		chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
		chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
		chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
		chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
		chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
		chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
		chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
		chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
		chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
		chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
		chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
		chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
		chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
		chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
		chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
		chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
		chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
		chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
		chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
		chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
		chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
		chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
		chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
		chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
		chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
		chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
		chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
		chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
		chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
		chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
		chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
		chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
		chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
		chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
		chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
		chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
		chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
		chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
		// Decompositions for Latin Extended-B
		chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
		chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
		// Euro Sign
		chr(226).chr(130).chr(172) => 'E',
		// GBP (Pound) Sign
		chr(194).chr(163) => '',
		// Vowels with diacritic (Vietnamese)
		// unmarked
		chr(198).chr(160) => 'O', chr(198).chr(161) => 'o',
		chr(198).chr(175) => 'U', chr(198).chr(176) => 'u',
		// grave accent
		chr(225).chr(186).chr(166) => 'A', chr(225).chr(186).chr(167) => 'a',
		chr(225).chr(186).chr(176) => 'A', chr(225).chr(186).chr(177) => 'a',
		chr(225).chr(187).chr(128) => 'E', chr(225).chr(187).chr(129) => 'e',
		chr(225).chr(187).chr(146) => 'O', chr(225).chr(187).chr(147) => 'o',
		chr(225).chr(187).chr(156) => 'O', chr(225).chr(187).chr(157) => 'o',
		chr(225).chr(187).chr(170) => 'U', chr(225).chr(187).chr(171) => 'u',
		chr(225).chr(187).chr(178) => 'Y', chr(225).chr(187).chr(179) => 'y',
		// hook
		chr(225).chr(186).chr(162) => 'A', chr(225).chr(186).chr(163) => 'a',
		chr(225).chr(186).chr(168) => 'A', chr(225).chr(186).chr(169) => 'a',
		chr(225).chr(186).chr(178) => 'A', chr(225).chr(186).chr(179) => 'a',
		chr(225).chr(186).chr(186) => 'E', chr(225).chr(186).chr(187) => 'e',
		chr(225).chr(187).chr(130) => 'E', chr(225).chr(187).chr(131) => 'e',
		chr(225).chr(187).chr(136) => 'I', chr(225).chr(187).chr(137) => 'i',
		chr(225).chr(187).chr(142) => 'O', chr(225).chr(187).chr(143) => 'o',
		chr(225).chr(187).chr(148) => 'O', chr(225).chr(187).chr(149) => 'o',
		chr(225).chr(187).chr(158) => 'O', chr(225).chr(187).chr(159) => 'o',
		chr(225).chr(187).chr(166) => 'U', chr(225).chr(187).chr(167) => 'u',
		chr(225).chr(187).chr(172) => 'U', chr(225).chr(187).chr(173) => 'u',
		chr(225).chr(187).chr(182) => 'Y', chr(225).chr(187).chr(183) => 'y',
		// tilde
		chr(225).chr(186).chr(170) => 'A', chr(225).chr(186).chr(171) => 'a',
		chr(225).chr(186).chr(180) => 'A', chr(225).chr(186).chr(181) => 'a',
		chr(225).chr(186).chr(188) => 'E', chr(225).chr(186).chr(189) => 'e',
		chr(225).chr(187).chr(132) => 'E', chr(225).chr(187).chr(133) => 'e',
		chr(225).chr(187).chr(150) => 'O', chr(225).chr(187).chr(151) => 'o',
		chr(225).chr(187).chr(160) => 'O', chr(225).chr(187).chr(161) => 'o',
		chr(225).chr(187).chr(174) => 'U', chr(225).chr(187).chr(175) => 'u',
		chr(225).chr(187).chr(184) => 'Y', chr(225).chr(187).chr(185) => 'y',
		// acute accent
		chr(225).chr(186).chr(164) => 'A', chr(225).chr(186).chr(165) => 'a',
		chr(225).chr(186).chr(174) => 'A', chr(225).chr(186).chr(175) => 'a',
		chr(225).chr(186).chr(190) => 'E', chr(225).chr(186).chr(191) => 'e',
		chr(225).chr(187).chr(144) => 'O', chr(225).chr(187).chr(145) => 'o',
		chr(225).chr(187).chr(154) => 'O', chr(225).chr(187).chr(155) => 'o',
		chr(225).chr(187).chr(168) => 'U', chr(225).chr(187).chr(169) => 'u',
		// dot below
		chr(225).chr(186).chr(160) => 'A', chr(225).chr(186).chr(161) => 'a',
		chr(225).chr(186).chr(172) => 'A', chr(225).chr(186).chr(173) => 'a',
		chr(225).chr(186).chr(182) => 'A', chr(225).chr(186).chr(183) => 'a',
		chr(225).chr(186).chr(184) => 'E', chr(225).chr(186).chr(185) => 'e',
		chr(225).chr(187).chr(134) => 'E', chr(225).chr(187).chr(135) => 'e',
		chr(225).chr(187).chr(138) => 'I', chr(225).chr(187).chr(139) => 'i',
		chr(225).chr(187).chr(140) => 'O', chr(225).chr(187).chr(141) => 'o',
		chr(225).chr(187).chr(152) => 'O', chr(225).chr(187).chr(153) => 'o',
		chr(225).chr(187).chr(162) => 'O', chr(225).chr(187).chr(163) => 'o',
		chr(225).chr(187).chr(164) => 'U', chr(225).chr(187).chr(165) => 'u',
		chr(225).chr(187).chr(176) => 'U', chr(225).chr(187).chr(177) => 'u',
		chr(225).chr(187).chr(180) => 'Y', chr(225).chr(187).chr(181) => 'y',
		// Vowels with diacritic (Chinese, Hanyu Pinyin)
		chr(201).chr(145) => 'a',
		// macron
		chr(199).chr(149) => 'U', chr(199).chr(150) => 'u',
		// acute accent
		chr(199).chr(151) => 'U', chr(199).chr(152) => 'u',
		// caron
		chr(199).chr(141) => 'A', chr(199).chr(142) => 'a',
		chr(199).chr(143) => 'I', chr(199).chr(144) => 'i',
		chr(199).chr(145) => 'O', chr(199).chr(146) => 'o',
		chr(199).chr(147) => 'U', chr(199).chr(148) => 'u',
		chr(199).chr(153) => 'U', chr(199).chr(154) => 'u',
		// grave accent
		chr(199).chr(155) => 'U', chr(199).chr(156) => 'u',
		);

		// Used for locale-specific rules
		/*$locale = get_locale();

		if ( 'de_DE' == $locale ) {
			$chars[ chr(195).chr(132) ] = 'Ae';
			$chars[ chr(195).chr(164) ] = 'ae';
			$chars[ chr(195).chr(150) ] = 'Oe';
			$chars[ chr(195).chr(182) ] = 'oe';
			$chars[ chr(195).chr(156) ] = 'Ue';
			$chars[ chr(195).chr(188) ] = 'ue';
			$chars[ chr(195).chr(159) ] = 'ss';
		} elseif ( 'da_DK' === $locale ) {
			$chars[ chr(195).chr(134) ] = 'Ae';
 			$chars[ chr(195).chr(166) ] = 'ae';
			$chars[ chr(195).chr(152) ] = 'Oe';
			$chars[ chr(195).chr(184) ] = 'oe';
			$chars[ chr(195).chr(133) ] = 'Aa';
			$chars[ chr(195).chr(165) ] = 'aa';
		}
*/
		$string = strtr($string, $chars);
	} else {
		$chars = array();
		// Assume ISO-8859-1 if not UTF-8
		$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
			.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
			.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
			.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
			.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
			.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
			.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
			.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
			.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
			.chr(252).chr(253).chr(255);

		$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

		$string = strtr($string, $chars['in'], $chars['out']);
		$double_chars = array();
		$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
		$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
		$string = str_replace($double_chars['in'], $double_chars['out'], $string);
	}

	return $string;
}


public static function seems_utf8($str) {
	self::mbstring_binary_safe_encoding();
	$length = strlen($str);
	self::mbstring_binary_safe_encoding(true);
	for ($i=0; $i < $length; $i++) {
		$c = ord($str[$i]);
		if ($c < 0x80) $n = 0; // 0bbbbbbb
		elseif (($c & 0xE0) == 0xC0) $n=1; // 110bbbbb
		elseif (($c & 0xF0) == 0xE0) $n=2; // 1110bbbb
		elseif (($c & 0xF8) == 0xF0) $n=3; // 11110bbb
		elseif (($c & 0xFC) == 0xF8) $n=4; // 111110bb
		elseif (($c & 0xFE) == 0xFC) $n=5; // 1111110b
		else return false; // Does not match any model
		for ($j=0; $j<$n; $j++) { // n bytes matching 10bbbbbb follow ?
			if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
				return false;
		}
	}
	return true;
}


public static function mbstring_binary_safe_encoding( $reset = false ) {
	static $encodings = array();
	static $overloaded = null;

	if ( is_null( $overloaded ) )
		$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );

	if ( false === $overloaded )
		return;

	if ( ! $reset ) {
		$encoding = mb_internal_encoding();
		array_push( $encodings, $encoding );
		mb_internal_encoding( 'ISO-8859-1' );
	}

	if ( $reset && $encodings ) {
		$encoding = array_pop( $encodings );
		mb_internal_encoding( $encoding );
	}
}


     



    public static function subscribeNewsletter($contact) {
        $newsletterids['en_US'] = 1;
        $newsletterids['fr_FR'] = 2;
        $now = new JDate();
//        file_put_contents('log.txt', print_r($contact, true));
        if(!isset($contact->contact_type)) {
            Factory::getApplication()->enqueueMessage("Unable to Register Contact to the Newsletter :: Function SubscribeNewsletter: Contact_type is not defined. Contact Admin", 'error');
            return false;
        }


        $db = Factory::getContainer()->get('DatabaseDriver');
        $name = (isset($contact->organization_name) && isset($contact->contact_type) && $contact->contact_type =='Organization') ? $contact->organization_name : $contact->first_name.' '.$contact->last_name;
        
        $newsletter_id = (isset($newsletterids[$contact->preferred_language])) ? $newsletterids[$contact->preferred_language] : 0;
        if ($newsletter_id == 0) {
            Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_NO_PREFERED_LANGUAGE',$name), 'warning');
            return false;
        }
        
        $mail = Bie_membersUtils::getMail($contact->contact_id);
        if (empty(trim($mail))) {
            Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_NO_MAIL',$contact->display_name), 'warning');
            return false;            
        }
        
        
        
//        $acymailid = Bie_membersUtils::contactInAcymail($contact);
//        if ($acymailid == 0) {
//           $acymailid = Bie_membersUtils::insertUserNewsletter($contact);
//        }
//
//        $rslt = Bie_membersUtils::alreadySubscribed($acymailid, $newsletter_id);
//        if ($rslt != 3) {
//            Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_ALREADY_INLIST_'.$rslt,$name), 'warning');
//            return false;
//        }

        $isIndividual = ($contact->contact_type =='Individual')? true : false;


        $obj = new stdClass();
        $obj->entity_id = $contact->contact_id;
        if ($isIndividual) {
            $obj->expo_news_27  = 2;
            $obj->subscription_date_29 = $now->toSql();
            $tbl = 'civicrm_value_contacts_2';
        } else {
            $obj->expo_news_28  = 2;
            $obj->subscription_date_31 = $now->toSql();
            $tbl = 'civicrm_value_organisaztion_name_en_1';
        }


        try {
            $res = $db->insertObject($tbl, $obj);
        }
        catch (JDatabaseExceptionExecuting $databaseException) {
            if ($databaseException->getCode() == 1062) {
                try  {
                    $db->updateObject($tbl,$obj,'entity_id');
                }
                catch (JDatabaseExceptionExecuting $dbe) {
                   Factory::getApplication()->enqueueMessage($dbe->getMessage(), 'warning');
                   return false;
                }
            } else {
                Factory::getApplication()->enqueueMessage($databaseException->getMessage(), 'warning');
                return false;
            }
        }
        Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_INSERT_SUCCESS',$name, JText::_('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_TITLE_'.$newsletter_id)), 'success');
        return true;        
    }

    public static function unsubscribeNewsletter($contact) {
        $newsletterids['en_US'] = 1;
        $newsletterids['fr_FR'] = 2;
        $now = new JDate();

        if(!isset($contact->contact_type)) {
            Factory::getApplication()->enqueueMessage("Unable to Un-Register Contact From the Newsletter :: Function UnsubscribeNewsletter: Contact_type is not defined. Contact Admin", 'error');
            return false;
        }

        $name = (isset($contact->organization_name) && isset($contact->contact_type) && $contact->contact_type =='Organization') ? $contact->organization_name : $contact->first_name.' '.$contact->last_name;
        $db = Factory::getContainer()->get('DatabaseDriver');

        $newsletter_id = (isset($newsletterids[$contact->preferred_language])) ? $newsletterids[$contact->preferred_language] : 0;
        if ($newsletter_id == 0) {
            Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_NO_PREFERED_LANGUAGE',$name), 'warning');
            return false;
        }
        
//        $acymailid = Bie_membersUtils::contactInAcymail($contact);
//        if ($acymailid == 0) {
//            Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_ALREADY_INLIST_3',$name), 'warning');
//            return false;
//        }
//
//        $rslt = Bie_membersUtils::alreadySubscribed($acymailid, $newsletter_id);
//        if ($rslt != 1) {
//            Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_ALREADY_INLIST_'.$rslt,$name), 'warning');
//            return false;
//        }

        $isIndividual = ($contact->contact_type =='Individual')? true : false;


        $obj = new stdClass();
        $obj->entity_id = $contact->contact_id;
        if ($isIndividual) {
            $obj->expo_news_27  = 3;
            $obj->unsubscription_date_30 = $now->toSql();
            $tbl = 'civicrm_value_contacts_2';
        } else {
            $obj->expo_news_28  = 3;
            $obj->unsubscribe_date_32 = $now->toSql();
            $tbl = 'civicrm_value_organisaztion_name_en_1';
        }

        try {
            $res = $db->insertObject($tbl, $obj);
        }
        catch (JDatabaseExceptionExecuting $databaseException) {
            if ($databaseException->getCode() == 1062) {
                try  {
                    $db->updateObject($tbl,$obj,'entity_id');
                }
                catch (JDatabaseExceptionExecuting $dbe) {
                    Factory::getApplication()->enqueueMessage($dbe->getMessage(), 'warning');
                    return false;
                }
            } else {
                Factory::getApplication()->enqueueMessage($databaseException->getMessage(), 'warning');
                return false;
            }
        }


        Factory::getApplication()->enqueueMessage(JText::sprintf('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_UNSUBSCRIBE_SUCCESS',$name, JText::_('COM_BIE_BULLETIN_FORM_LBL_BULLETININDIVIDUAL_NEWSLETTER_TITLE_'.$newsletter_id)), 'success');
        return true;        
    }

    
    public static function contactInAcymail($contact,$db_name = '#__acym_user') {
        
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query
            ->select('id')
            ->from($db->quoteName($db_name))
            ->where($db->quoteName('cms_id') . ' = '. $db->quote($contact->contact_id))
             ->setLimit('1');
        $db->setQuery($query);
        $rslt = $db->loadResult();
        
        return (intval($rslt) > 0) ? intval($rslt) : 0;
    }

    public static function alreadySubscribed($acymailid,$newsletter_id) {
        
        //To-do check if users already Exists in selected $newsletter_id;
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query
            ->select('status')
            ->from($db->quoteName('#__acym_user_has_list'))
            ->where($db->quoteName('user_id') . ' = '. $db->quote($acymailid))
            ->where($db->quoteName('list_id') . ' = '. $db->quote($newsletter_id))
            ->setLimit('1');
        $db->setQuery($query);
        $db->execute();
        $num_rows = $db->getNumRows();        
        //Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');
        if($num_rows > 0) {
            $rslt = $db->loadResult();
            return intval($rslt);
        } 
        
        return 3;
    }
    
    

    public static function insertUserNewsletter($contact,$db_name = '#__acym_user') {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $now = new JDate();
            
            $key = Bie_membersUtils::acymailing_generateKey(14);
            $mail = Bie_membersUtils::getMail($contact->contact_id);
            
            $name = (isset($contact->organization_name) && isset($contact->contact_type) && $contact->contact_type =='Organization') ? $contact->organization_name : $contact->first_name.' '.$contact->last_name;
            $columns = array('email', 'name','creation_date','confirmed','active','key','cms_id');
            $values = array($db->quote($mail), $db->quote($name), $db->quote($now->toSql()),$db->quote(1),$db->quote(1),$db->quote($key),$db->quote($contact->contact_id));
            $query = $db->getQuery(true);
            $query->insert($db->quoteName($db_name))
                   ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));
            $db->setQuery($query);
            $db->execute();
            return $db->insertid();      
    }


	public static function updateContactInAcymail($contact,$acyMailUserID = 0,$db_name = '#__acym_user'){
        $db = Factory::getContainer()->get('DatabaseDriver');
		$name = (isset($contact->organization_name) && isset($contact->contact_type) && $contact->contact_type =='Organization') ? $contact->organization_name : $contact->first_name.' '.$contact->last_name;
		$mail = Bie_membersUtils::getMail($contact->contact_id);
		$acyUser = new stdClass();
		$acyUser->id = $acyMailUserID;
		$acyUser->email = $mail;
		$acyUser->name =  $name;

		$result = $db->updateObject($db_name, $acyUser, 'id');

		if($contact->is_deceased == 1) {
			$acyUserList = new stdClass();
			$acyUserList->user_id = $acyMailUserID;
			$acyUserList->status = 0;
			$date = new Date('now');
			$acyUserList->unsubscribe_date =  $date->toSql();
			$result = $db->updateObject('#__acym_user_has_list', $acyUserList, 'user_id');
		}

    }


    public static function synchroniseSubscribers() {
	    $now = new JDate();
        $db = Factory::getContainer()->get('DatabaseDriver');

        $newsletterids['en_US'] = 1;
        $newsletterids['fr_FR'] = 2;


        $query = "DELETE l.* FROM biecrm_acym_user_has_list AS l "
                ."JOIN biecrm_acym_user AS sub ON l.user_id = sub.id "
                ."WHERE l.list_id != 4";
        $db->setQuery($query);
        $db->execute();

        $query = "DELETE u.* FROM `biecrm_acym_user` as u "
                ."LEFT JOIN biecrm_acym_user_has_list as l ON u.id = l.user_id "
                ."where isNULL(l.list_id)";
        $db->setQuery($query);
        $db->execute();

        //Insert Individuals
        $query = "SELECT c.id,c.first_name,c.last_name,c.preferred_language,c.is_deceased,v.entity_id ".
                 "FROM `civicrm_contact` as c ".
                 "INNER JOIN civicrm_value_contacts_2 as v ON c.id = v.entity_id ".
                 "where expo_news_27 = 2";
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        foreach ($rows as $row) {
            if (!($row->is_deceased == 1)) {
                Bie_membersUtils::registerAcyMailUserAndLinkToList($row->first_name." ".$row->last_name,$row->entity_id,$newsletterids[$row->preferred_language],'biecrm_acym_user','biecrm_acym_user_has_list');
            }
        }

        //Insert Organisations
        $query = "SELECT c.id,c.display_name,c.preferred_language,c.is_deceased,v.entity_id ".
            "FROM `civicrm_contact` as c ".
            "INNER JOIN civicrm_value_organisaztion_name_en_1 as v ON c.id = v.entity_id ".
            "where expo_news_28 = 2";
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        foreach ($rows as $row) {
            if (!($row->is_deceased == 1)) {
                Bie_membersUtils::registerAcyMailUserAndLinkToList($row->display_name,$row->entity_id,$newsletterids[$row->preferred_language],'biecrm_acym_user','biecrm_acym_user_has_list');
            }
        }


    }



    public static function registerAcyMailUserAndLinkToList($userName,$userID, $mailingList, $tbl_user,$tbl_list) {
        $now = new JDate();
        $db = Factory::getContainer()->get('DatabaseDriver');

        $obj = new stdClass();
        $obj->id = $userID;
        $obj->name = $userName;
        $obj->email  = Bie_membersUtils::getMail($userID);
        $obj->creation_date = $now->toSql();
        $obj->active = 1;
        $obj->confirmed = 1;
        $obj->source = "CRM";
        $obj->key = Bie_membersUtils::acymailing_generateKey(14);
        $obj->tracking = 1;

        try {
            $res = $db->insertObject($tbl_user, $obj);
            if ($res) {
                $r = Bie_membersUtils::AcyMailAssociateList($userID,$mailingList,$tbl_list);
            }
        }
        catch (JDatabaseExceptionExecuting $databaseException) {
            if ($databaseException->getCode() == 1062) {
                try  {
                    $res = $db->updateObject($tbl_user,$obj,'id');
                }
                catch (JDatabaseExceptionExecuting $dbe) {
                    Factory::getApplication()->enqueueMessage("Unable to Update AcyMail User :: ".$obj->name." - Error Code::: ".$dbe->getCode()." Message ::".$dbe->getMessage() , 'warning');
                }
                if ($res) {
                    Bie_membersUtils::AcyMailAssociateList($userID,$mailingList,$tbl_list);
                }
            } else {
                Factory::getApplication()->enqueueMessage("Create AcyMail User :: ".$obj->name." - Error Code::: ".$databaseException->getCode()." Message ::".$databaseException->getMessage() , 'warning');
            }
        }

    }

    public static function AcyMailAssociateList($userID,$listID,$tbl_list) {
        $now = new JDate();
        $db = Factory::getContainer()->get('DatabaseDriver');

        $list = new stdClass();
        $list->user_id = $userID;
        $list->status = 1;
        $list->list_id = $listID;
        $list->subscription_date = $now->toSql();
        try {
            $res = $db->insertObject($tbl_list, $list,);
            return true;
        }
        catch (JDatabaseExceptionExecuting $dbE){
            Factory::getApplication()->enqueueMessage("Assign Mailing list Error:: ".$dbE->getMessage(), 'warning');
            return false;
        }

    }

   public static function getMail($cID)  {

    $db = Factory::getContainer()->get('DatabaseDriver');
    $query = $db->getQuery(true);
        $query
            ->select('email')
            ->from($db->quoteName('civicrm_email'))
            ->where($db->quoteName('contact_id') . ' = '. $db->quote($cID))
            ->where($db->quoteName('is_primary') . ' = '. $db->quote(1))
             ->setLimit('1');
        
        $db->setQuery($query);
        return $db->loadResult();

   }   
    
  
   public static function checkStatusNewsletter($cms_id,$list_id) {

    $db = Factory::getContainer()->get('DatabaseDriver');
    $query = $db->getQuery(true);

        //Check Individuals First
        $query->select(array('a.`expo_news_27`'))
            ->from($db->quoteName('civicrm_value_contacts_2','a'))
            ->where($db->quoteName('a.entity_id') . ' = '. $db->quote($cms_id));
        $db->setQuery($query);
        $db->execute();
        $num_rows = $db->getNumRows();
//        Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');
        if($num_rows > 0) {
            $rslt = $db->loadResult();
        } else {
            //Check Organisations
            $query = $db->getQuery(true);
            $query->select(array('a.`expo_news_28`'))
                ->from($db->quoteName('civicrm_value_organisaztion_name_en_1','a'))
                ->where($db->quoteName('a.entity_id') . ' = '. $db->quote($cms_id));
            $db->setQuery($query);
            $rslt = $db->loadResult();
        }

       switch (intval($rslt)) {
           case 2:
               return 1;
           case 3:
               return 0;
           default:
               return 3;
       }

   }

//    public static function checkStatusNewsletter($cms_id,$list_id) {
//
//        $mails = Bie_membersUtils::getContactMails($cms_id,false,",");
//        if ($mails == false) {
//            return 3;
//        }
//
//        $db = Factory::getDbo();
//        $query = $db->getQuery(true);
//        $query->select(array('b.`status`'))
//            ->from($db->quoteName('#__acym_user','a'))
//            ->join('LEFT', $db->quoteName('#__acym_user_has_list', 'b') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.user_id') . ')')
//            ->where($db->quoteName('b.list_id') . ' = '. $db->quote($list_id));
//
//        if ($mails !== false) {
//            $query->where($db->quoteName('a.email') . ' IN ('. $mails.')');
//        }
//
//        $db->setQuery($query);
//        $db->execute();
//        $num_rows = $db->getNumRows();
//        //Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');
//        if($num_rows > 0) {
//            $rslt = $db->loadResult();
//            return intval($rslt);
//        }
//
//        return 3;
//    }

    public static function getContactMails($cID,$beautify = true, $separator = "<br/>")  {

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query
            ->select('email')
            ->from($db->quoteName('civicrm_email'))
            ->where($db->quoteName('contact_id') . ' = '. $db->quote($cID));

        $db->setQuery($query);
        $column= $db->loadColumn();
        $arr = array();
        foreach ($column as $item) {
            if ($beautify) {
                $arr[] = JText::sprintf('COM_BIE_MEMBERS_DELEGATES_MAIL',$item,$item);
            } else {
                $arr[] =  $db->quote($item);
            }
        }
        if (count($arr) > 0) {
            return implode($separator, $arr);
        } else {
            return false;
        }

    }

}
