<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-consul-patch.
 *
 * @link     https://github.com/8591/friendsofhyperf/service-governance-consul-patch
 * @document https://github.com/8591/friendsofhyperf/service-governance-consul-patch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceConsulPatch;

class ConfigProvider
{
    public function __invoke(): array
    {
        defined('BASE_PATH') or define('BASE_PATH', __DIR__);

        $listeners = [];

        if (class_exists('Hyperf\RpcMultiplex\Listener\RegisterServiceListener')) {
            $listeners[] = 'Hyperf\RpcMultiplex\Listener\RegisterServiceListener';
        }

        return [
            'dependencies' => [
                \Hyperf\ServiceGovernance\Listener\RegisterServiceListener::class => Listener\RegisterServiceListener::class,
                \Hyperf\ServiceGovernanceConsul\ConsulDriver::class => ConsulDriver::class,
                ConsulHealth::class => ConsulHealthFactory::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'commands' => [],
            'listeners' => $listeners,
            'publish' => [],
            'signal' => [
                'handlers' => [
                    Handler\DeregisterServicesHandler::class => PHP_INT_MIN,
                ],
            ],
        ];
    }
}
