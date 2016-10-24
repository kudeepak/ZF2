<?php
namespace Login;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Router\RouteMatch;

class Module implements AutoloaderProviderInterface
{

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $serviceManager = $e->getApplication()->getServiceManager();
        $auth = $serviceManager->get('AuthService');
        
        $config = $serviceManager->get('Config');
        $list = $config['whitelist_url'];
        
        $eventManager->attach(MvcEvent::EVENT_ROUTE, function ($e) use($list, $auth) {
            $match = $e->getRouteMatch();
            
            // No route match, this is a 404
            if (! $match instanceof RouteMatch) {
                return;
            }
            
            // Route is whitelisted
            $name = $match->getMatchedRouteName();
            
            if (in_array($name, $list)) {
                return;
            }
            
            // User is authenticated
            if ($auth->hasIdentity()) {
                return;
            }
            
            // Redirect to the user login page
            $router = $e->getRouter();
            $url = $router->assemble(array(), array(
                'name' => 'login'
            ));
            
            $response = $e->getResponse();
            $response->getHeaders()
                ->addHeaderLine('Location', $url);            
            $response->setStatusCode(302);
            
            return $response;
        }, -100);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                )
            )
        );
    }

    public function getServiceConfig()
    {   
        return array(
            'factories' => array(
                'Login\Model\AuthStorage' => function ($sm) {
                    return new \Login\Model\AuthStorage('zfs_login');
                },
                'AuthService' => function ($sm) {
                   
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $dbTableAuthAdapter = new DbTableAuthAdapter($dbAdapter, 'user', 'username', 'password', 'MD5(?)');
                    $authService = new AuthenticationService();
                    $authService->setAdapter($dbTableAuthAdapter);
                    $authService->setStorage($sm->get('Login\Model\AuthStorage'));
                    return $authService;
                }
            )
        );
    }

    public function init(ModuleManager $manager)
    {  
        $events = $manager->getEventManager();
        $sharedEvents = $events->getSharedManager();
        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function ($e) {
            $controller = $e->getTarget();
            // if (get_class($controller) == 'Login\Controller\IndexController') {
            $controller->layout('layout/login');
            // }
        }, 100);
    }
}
