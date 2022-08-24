<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-consul-patch.
 *
 * @link     https://github.com/8591/friendsofhyperf/service-governance-consul-patch
 * @document https://github.com/8591/friendsofhyperf/service-governance-consul-patch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceConsulPatch\Listener;

use Hyperf\Consul\Exception\ServerException;
use Hyperf\Framework\Event\MainWorkerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Hyperf\Utils\Coroutine;

class RegisterServiceListener extends \Hyperf\ServiceGovernance\Listener\RegisterServiceListener
{
    /**
     * @param MainCoroutineServerStart|MainWorkerStart $event
     */
    public function process(object $event): void
    {
        $reRegisterInterval = (int) $this->config->get('services.drivers.consul.check.re_register_interval', 10);

        Coroutine::create(function () use ($reRegisterInterval) {
            while (1) {
                try {
                    $services = $this->serviceManager->all();
                    $servers = $this->getServers();
                    foreach ($services as $serviceName => $serviceProtocols) {
                        foreach ($serviceProtocols as $paths) {
                            foreach ($paths as $service) {
                                if (! isset($service['publishTo'], $service['server'])) {
                                    continue;
                                }
                                [$address, $port] = $servers[$service['server']];
                                if ($governance = $this->governanceManager->get($service['publishTo'])) {
                                    if (! $governance->isRegistered($serviceName, $address, (int) $port, $service)) {
                                        $governance->register($serviceName, $address, (int) $port, $service);
                                    }
                                }
                            }
                        }
                    }
                } catch (ServerException $throwable) {
                    $this->logger->warning(sprintf(
                        'Cannot register service, %s, re-register after %s seconds.',
                        $throwable->getMessage(),
                        $reRegisterInterval
                    ));
                }

                sleep($reRegisterInterval);
            }
        });
    }
}
