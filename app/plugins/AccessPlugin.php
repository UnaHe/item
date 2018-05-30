<?php

use Phalcon\Acl;
use Phalcon\Acl\Role;
use Phalcon\Acl\Resource;
use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Acl\Adapter\Memory as AclList;

/**
 * SecurityPlugin
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class AccessPlugin extends Plugin
{
    const ROLE_GUEST = 'guest';
    public static $publicResources = [
        'passport' => [
            'wx',
            'login',
            'logout',
            'changepass'
        ],
        'errors' => [
            'show401',
            'show404',
            'show500'
        ],
        'api'=>[
            'ajaxinfraredareaupdate'
        ]
    ];

    public static $commonResources = ['index', 'upload','project'];

    /**
     * Returns an existing or new access control list
     *
     * @return s AclList
     */
    public function getAcl($userRole, $project_id)
    {

        // throw new \Exception("something");
        $aclName = 'item_acl_' . $userRole . $project_id;
        // $this->session->remove ( 'SecurityPlugin' );
        // unset ( $this->persistent->$aclName );

        if (1 || !isset ($this->persistent->$aclName)) {
            $acl = new AclList ();

            $acl->setDefaultAction(Acl::DENY);

            $acl->addRole($userRole);

            $accessModel = new AccessModel();
            $permissions = $accessModel->getList($userRole, $project_id);
            foreach (self::$publicResources as $resource => $actions) {
                if (!$acl->isResource($resource)) {
                    $acl->addResource(new Resource ($resource), $actions);
                } else {
                    $acl->addResourceAccess($resource, $actions);
                }
            }
            foreach (self::$commonResources as $resource) {
                $acl->addResource(new Resource ($resource), []);
            }

            $controllerDir = APP_PATH . 'app/controllers/';
            $files = scandir($controllerDir);
            $privateResources = [];
            foreach ($files as $v) {
                if ($v == '.' || $v == '..') {
                    continue;
                }
                $_controllerName = strtolower(str_replace('Controller.php', '', $v));
                if (array_key_exists($_controllerName, self::$publicResources)) {
                    continue;
                }
                $_controller = file_get_contents($controllerDir . '/' . $v);
                preg_match_all('/function (.+)Action\(\)/', $_controller, $controllerActions);
                if (!empty($controllerActions)) {
                    $privateResources[$_controllerName] = $controllerActions[1];
                }
            }
            foreach ($privateResources as $resource => $actions) {
                if (!$acl->isResource($resource)) {
                    $acl->addResource(new Resource ($resource), $actions);
                } else {
                    $acl->addResourceAccess($resource, $actions);
                }
            }
            foreach ($privateResources as $resource => $actions) {
                foreach ($actions as $action) {
                    foreach ($permissions as $p) {
                        if (!is_null($p ['resource'])) {
                            if (!array_key_exists($p ['resource'], $privateResources)) {
                                $acl->deny($p ['role'], $resource, $action);
                                continue;
                            }
                            if ($p ['resource'] == $resource) {
                                if (is_null($p ['operation']) || $p ['operation'] == $action) {
                                    switch ($p ['rule']) {
                                        case 'allow' :
                                            $acl->allow($p ['role'], $resource, $action);
                                            break;
                                        default :
                                            $acl->deny($p ['role'], $resource, $action);
                                            break;
                                    }
                                }
                            }
                        } elseif (is_null($p ['resource'])) {
                            if (is_null($p ['operation']) || $p ['operation'] == $action) {
                                switch ($p ['rule']) {
                                    case 'allow' :
                                        $acl->allow($p ['role'], $resource, $action);
                                        break;
                                    default :
                                        $acl->deny($p ['role'], $resource, $action);
                                        break;
                                }
                            }
                        }
                    }
                }
            }
            foreach (self::$commonResources as $v) {
                if (array_key_exists($v, $privateResources) && $userRole != self::ROLE_GUEST) {
                    $acl->allow($userRole, $v, $privateResources[$v]);
                } else {
                    $acl->deny($userRole, $v, []);
                }
            }


            // die();


            // Grant access to public areas to both users and guests
            // foreach ( $roles as $role ) {
            foreach (self::$publicResources as $resource => $actions) {
                foreach ($actions as $action) {
                    $acl->allow($userRole, $resource, $action);
                }
            }
            // }


            // The acl is stored in session, APC would be useful here too

            $this->persistent->$aclName = $acl;
        }

        return $this->persistent->$aclName;
    }

    /**
     * This action is executed before execute any action in the application
     *
     * @param Event $event
     * @param Dispatcher $dispatcher
     */
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {

        $user = $this->session->get('user');

        if (!$user) {
            $role = self::ROLE_GUEST;
            $project_id = 0;
        } else {
            $role = isset($user ['item_account_group_role']) ? $user['item_account_group_role'] : 'administrator';
            $project_id = isset($user['project_id']) ? $user['project_id'] : 0;
        }

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        if($role == 'administrator'){
            $acl = $this->getAcl($role, 0);
        }else{
            $acl = $this->getAcl($role, $project_id);
        }


        if (!$acl->isResource($controller)) {
            $dispatcher->forward(array(
                'controller' => 'errors',
                'action' => 'show404'
            ));
            return false;
        }

        $allowed = $acl->isAllowed($role, $controller, $action);
        if (!$allowed) {
            if ($role == self::ROLE_GUEST) {
                $dispatcher->forward(array(
                    'controller' => 'passport',
                    'action' => 'login'
                ));
            } else {
                $dispatcher->forward(array(
                    'controller' => 'errors',
                    'action' => 'show401'
                ));
            }
            return false;
        }
    }
}
