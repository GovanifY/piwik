<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreHome;

use Piwik\Db;
use Piwik\Menu\MenuTop;
use Piwik\Menu\MenuUser;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugins\UsersManager\API as APIUsersManager;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        $login = Piwik::getCurrentUserLogin();
        $user  = APIUsersManager::getInstance()->getUser($login);

        if (!empty($user['alias'])) {
            $login = $user['alias'];
        }

        if (Plugin\Manager::getInstance()->isPluginActivated('Feedback')) {
            $menu->addItem('icon-help', null, array('module' => 'Feedback', 'action' => 'index'), $order = 990, 'Help');
        }

        if (Piwik::isUserIsAnonymous()) {
            if (Plugin\Manager::getInstance()->isPluginActivated('Feedback')) {
                $menu->addItem('icon-user', null, array('module' => 'Feedback', 'action' => 'index'), 970, $login);
            } else {
                $menu->addItem('icon-user', null, array('module' => 'API', 'action' => 'listAllAPI'), 970, $login);
            }
        } else {
            $menu->addItem('icon-user', null, array('module' => 'UsersManager', 'action' => 'userSettings'), 970, $login);
        }

        $module = $this->getLoginModule();
        if (Piwik::isUserIsAnonymous()) {
            $menu->addItem('Login_LogIn', null, array('module' => $module, 'action' => false), 1000);
        } else {
            $menu->addItem('icon-sign-out', null, array('module' => $module, 'action' => 'logout', 'idSite' => null), 1000, 'Sign out');
        }
    }

    public function configureUserMenu(MenuUser $menu)
    {
        $menu->addPersonalItem(null, array(), 1, false);
        $menu->addManageItem(null, array(), 2, false);
        $menu->addPlatformItem(null, array(), 3, false);
    }

    private function getLoginModule()
    {
        return Piwik::getLoginPluginName();
    }

}
