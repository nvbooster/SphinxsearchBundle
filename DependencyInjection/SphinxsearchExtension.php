<?php

namespace IAkumaI\SphinxsearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;
use IAkumaI\SphinxsearchBundle\Doctrine\Bridge;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author nvb <nvb@aproxima.ru>
 *
 */
class SphinxsearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');

        if (!empty($config['searchd'])) {
            $container->getDefinition(Sphinxsearch::class)
                 ->setArguments([
                     '$host' => $config['searchd']['host'],
                     '$port' => $config['searchd']['port'],
                     '$socket' => $config['searchd']['socket'],
                ]);
        }

        $container->setParameter('iakumai.sphinxsearch.indexes', empty($config['indexes']) ? [] : $config['indexes']);

        if (!empty($config['bridge'])) {
            $container->getDefinition(Sphinxsearch::class)
                ->addMethodCall('setBridge', [new Reference($config['bridge'])])
            ;
        }

        if (!empty($config['doctrine_bridge']['entity_manager'])) {
            $container->getDefinition(Bridge::class)
                ->setArgument('$em', new Reference($config['doctrine_bridge']['entity_manager']))
            ;
        }
        $container->getDefinition(Bridge::class)
            ->setArgument('$indexes', '%iakumai.sphinxsearch.indexes%')
        ;
    }
}
