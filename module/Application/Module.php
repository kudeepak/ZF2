<?php
namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Config\SessionConfig;
use Zend\Session\Container;
use Zend\Session\SessionManager;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as LogWriterStream;
use Zend\Log\Formatter\Simple;

class Module
{

    public function onBootstrap(MvcEvent $e)
    {
       
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $serviceManager = $e->getApplication()->getServiceManager();
        $config = $serviceManager->get('Config');
        
        // initSession function called for Session Config and Start
        $this->initSession($config);
        
        // Logger implemeting to log the error which is trown from the application
        $logger = $serviceManager->get('Zend\Log');
        $logger->registerErrorHandler($logger);
        $logger->registerExceptionHandler($logger);
        register_shutdown_function(function () use($logger) {
            if ($error = error_get_last()) {
                if (($error['type'] & error_reporting())) {
                    $logger->ERR($error['message'] . " in " . $error['file'] . ' line ' . $error['line']);
                    $logger->__destruct();
                }
            }
        });         
    }

    public function initSession($config)
    {
        
        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($config['session']);
        $sessionManager = new SessionManager($sessionConfig);
        
        $dbAdapter = new \Zend\Db\Adapter\Adapter($config['db']);
        
        $sessionOptions = new \Zend\Session\SaveHandler\DbTableGatewayOptions(null);
        
        $sessionTableGateway = new \Zend\Db\TableGateway\TableGateway('session', $dbAdapter);
        $saveHandler = new \Zend\Session\SaveHandler\DbTableGateway($sessionTableGateway, $sessionOptions);
        $sessionManager = new \Zend\Session\SessionManager(NULL, NULL, $saveHandler);
        
        $sessionManager->start();
        Container::setDefaultManager($sessionManager);
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
                'Zend\Log' => function ($sm) {
                    
                    $fileName = 'log_' . date('m-d-Y') . '.txt';
                    $log = new Logger();
                    $writer = new LogWriterStream('./data/logs/' . $fileName);
                    $log->addWriter($writer);
                    $format = '%timestamp%
                    %priorityName% (%priority%): %message%' . PHP_EOL;
                    $formatter = new Simple($format);
                    $writer->setFormatter($formatter);
                    return $log;
                }
            )
        );
    }
}
