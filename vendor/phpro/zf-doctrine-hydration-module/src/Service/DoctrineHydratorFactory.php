<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Phpro\DoctrineHydrationModule\Service;

use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Stdlib\Hydrator;
use Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator;
use Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use Zend\Stdlib\Hydrator\StrategyEnabledInterface;

/**
 * Class DoctrineHydratorFactory
 *
 * @package Phpro\DoctrineHydrationModule\Service
 */
class DoctrineHydratorFactory implements AbstractFactoryInterface
{

    const FACTORY_NAMESPACE = 'doctrine-hydrator';


    const OBJECT_MANAGER_TYPE_ODM_MONGODB = 'ODM/MongoDB';
    const OBJECT_MANAGER_TYPE_ORM = 'ORM';

    /**
     * Cache of canCreateServiceWithName lookups
     * @var array
     */
    protected $lookupCache = array();

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $hydratorManager
     * @param                         $name
     * @param                         $requestedName
     *
     * @return bool
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $hydratorManager, $name, $requestedName)
    {
        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        $serviceManager = $hydratorManager->getServiceLocator();

        if (!$serviceManager->has('Config')) {
            return false;
        }

        // Validate object is set
        $config = $serviceManager->get('Config');
        $namespace = self::FACTORY_NAMESPACE;
        if (!isset($config[$namespace]) || !is_array($config[$namespace]) || !isset($config[$namespace][$requestedName])) {
            $this->lookupCache[$requestedName] = false;
            return false;
        }

        // Validate object manager
        $config = $config[$namespace];
        if (!isset($config[$requestedName]) || !isset($config[$requestedName]['object_manager'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "object_manager" is specified for hydrator %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        // Validate object class
        if (!isset($config[$requestedName]['entity_class'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "entity_class" is specified for hydrator %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        $this->lookupCache[$requestedName] = true;
        return true;
    }

    /**
     * @param ServiceLocatorInterface $hydratorManager
     * @param                         $name
     * @param                         $requestedName
     *
     * @return DoctrineHydrator
     */
    public function createServiceWithName(ServiceLocatorInterface $hydratorManager, $name, $requestedName)
    {
        $serviceManager = $hydratorManager->getServiceLocator();

        $config   = $serviceManager->get('Config');
        $config   = $config[self::FACTORY_NAMESPACE][$requestedName];

        $objectManager = $this->loadObjectManager($serviceManager, $config);
        $doctrineModuleHydrator = $this->loadDoctrineModuleHydrator($serviceManager, $config, $objectManager);

        $useDoctrineHydrator = (array_key_exists('use_generated_hydrator', $config) && $config['use_generated_hydrator']);
        $entityHydrator = null;
        if ($useDoctrineHydrator) {
            $entityHydrator = $this->loadEntityHydrator($serviceManager, $config, $objectManager);
        }

        $extractService = $doctrineModuleHydrator;
        $hydrateService = $doctrineModuleHydrator;
        if ($entityHydrator) {
            $hydrateService = $entityHydrator;
        }

        $hydrator = new DoctrineHydrator($extractService, $hydrateService);
        return $hydrator;
    }

    /**
     * @param $objectManager
     *
     * @return string
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function getObjectManagerType($objectManager)
    {
        if (class_exists('\\Doctrine\\ODM\\MongoDB\\DocumentManager')
            && $objectManager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            return self::OBJECT_MANAGER_TYPE_ODM_MONGODB;
        } elseif (class_exists('\\Doctrine\\ORM\\EntityManager')
            && $objectManager instanceof \Doctrine\ORM\EntityManager) {
            return self::OBJECT_MANAGER_TYPE_ORM;
        }

        throw new ServiceNotCreatedException('Unknown object manager type: ' . get_class($objectManager));
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     *
     * @return ObjectManager
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function loadObjectManager(ServiceLocatorInterface $serviceManager, $config)
    {
        if (!$serviceManager->has($config['object_manager'])) {
            throw new ServiceNotCreatedException('The object_manager could not be found.');
        }

        $objectManager = $serviceManager->get($config['object_manager']);
        return $objectManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     * @param                         $objectManager
     *
     * @return null|HydratorInterface
     */
    protected function loadEntityHydrator(ServiceLocatorInterface $serviceManager, $config, $objectManager)
    {
        $objectManagerType = $this->getObjectManagerType($objectManager);
        if ($objectManagerType != self::OBJECT_MANAGER_TYPE_ODM_MONGODB) {
            return null;
        }

        $hydratorFactory = $objectManager->getHydratorFactory();
        $hydrator = $hydratorFactory->getHydratorFor($config['entity_class']);

        // Configure hydrator:
        $this->configureHydratorStrategies($hydrator, $serviceManager, $config, $objectManager);

        return $hydrator;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     * @param ObjectManager $objectManager
     *
     * @return HydratorInterface
     */
    protected function loadDoctrineModuleHydrator(ServiceLocatorInterface $serviceManager, $config, $objectManager)
    {
        $objectManagerType = $this->getObjectManagerType($objectManager);

        if ($objectManagerType == self::OBJECT_MANAGER_TYPE_ODM_MONGODB) {
            $hydrator = new MongoDB\DoctrineObject($objectManager, $config['by_value']);
        } else {
            $hydrator = new Hydrator\DoctrineObject($objectManager, $config['by_value']);
        }

        $this->configureHydratorStrategies($hydrator, $serviceManager, $config, $objectManager);
        return $hydrator;
    }

    /**
     * @param                         $hydrator
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     * @param                         $objectManager
     *
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function configureHydratorStrategies($hydrator, ServiceLocatorInterface $serviceManager, $config, $objectManager)
    {
        if (!($hydrator instanceof StrategyEnabledInterface) || !isset($config['strategies'])) {
            return;
        }

        foreach ($config['strategies'] as $field => $strategyKey) {
            if (!$serviceManager->has($strategyKey)) {
                throw new ServiceNotCreatedException(sprintf('Invalid strategy %s for field %s', $strategyKey, $field));
            }

            $strategy = $serviceManager->get($strategyKey);
            if (!$strategy instanceof StrategyInterface) {
                throw new ServiceNotCreatedException(sprintf('Invalid strategy class %s for field %s', get_class($strategy), $field));
            }

            // Attach object manager:
            if ($strategy instanceof ObjectManagerAwareInterface) {
                $strategy->setObjectManager($objectManager);
            }

            $hydrator->addStrategy($field, $strategy);
        }
    }
}
