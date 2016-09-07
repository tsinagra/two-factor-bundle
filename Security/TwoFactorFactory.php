<?php
namespace Scheb\TwoFactorBundle\Security;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class TwoFactorFactory implements SecurityFactoryInterface
{
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
            ->scalarNode('auth_form_path')->defaultValue('/2fa')
            ->end();
    }

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $entryPointId = $this->createEntryPoint($container, $id, $config);
        $providerId = $this->createAuthenticationProvider($container, $id, $userProvider);
        $listenerId = $this->createAuthenticationListener($container, $id, $entryPointId);

        return array($providerId, $listenerId, $entryPointId);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param array $config
     *
     * @return string
     */
    private function createEntryPoint(ContainerBuilder $container, $id, $config)
    {
        $entryPointId = 'security.authentication.two_factor_entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('scheb_two_factor.authentication.entry_point'))
            ->replaceArgument(1, $config['auth_form_path']);

        return $entryPointId;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param string $userProvider
     *
     * @return string
     */
    private function createAuthenticationProvider(ContainerBuilder $container, $id, $userProvider)
    {
        $providerId = 'security.authentication.provider.two_factor.' . $id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('scheb_two_factor.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProvider));

        return $providerId;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param string $entryPointId
     *
     * @return string
     */
    private function createAuthenticationListener(ContainerBuilder $container, $id, $entryPointId)
    {
        $listenerId = 'security.authentication.listener.two_factor.' . $id;
        $container
            ->setDefinition($listenerId, new DefinitionDecorator('scheb_two_factor.security.authentication.listener'))
            ->replaceArgument(2, new Reference($entryPointId));

        return $listenerId;
    }

    public function getPosition()
    {
        return 'remember_me';
    }

    public function getKey()
    {
        return 'two-factor';
    }
}
