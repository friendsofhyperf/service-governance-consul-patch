<?php

declare(strict_types=1);
/**
 * This file is part of reporting.
 *
 * @link     https://github.com/8591/friendsofhyperf/service-governance-consul-patch
 * @document https://github.com/8591/friendsofhyperf/service-governance-consul-patch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceConsulPatch;

use Hyperf\Consul\Health;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;

class ConsulHealthFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Health(function () use ($container) {
            $config = $container->get(ConfigInterface::class);
            $token = $config->get('services.drivers.consul.token', '');
            $options = [
                'timeout' => 2,
                'base_uri' => $config->get('services.drivers.consul.uri', Health::DEFAULT_URI),
            ];

            if (! empty($token)) {
                $options['headers'] = [
                    'X-Consul-Token' => $token,
                ];
            }

            return $container->get(ClientFactory::class)->create($options);
        });
    }
}
