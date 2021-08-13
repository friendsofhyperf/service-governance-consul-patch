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

use Hyperf\Contract\ConfigInterface;

class ConsulDriver extends \Hyperf\ServiceGovernanceConsul\ConsulDriver
{
    public function register(string $name, string $host, int $port, array $metadata): void
    {
        if (isset($this->config) && $this->config instanceof ConfigInterface) {
            /** @var ConfigInterface $config */
            $config = $this->config;
        } else {
            $config = $this->container->get(ConfigInterface::class);
        }
        $deregisterCriticalServiceAfter = $config->get('services.drivers.consul.check.deregister_critical_service_after', '90m');
        $interval = $config->get('services.drivers.consul.check.interval', '1s');
        $nextId = empty($metadata['id']) ? $this->generateId($this->getLastServiceId($name)) : $metadata['id'];
        $protocol = $metadata['protocol'];
        $requestBody = [
            'Name' => $name,
            'ID' => $nextId,
            'Address' => $host,
            'Port' => $port,
            'Meta' => [
                'Protocol' => $protocol,
            ],
        ];
        if ($protocol === 'jsonrpc-http') {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => $deregisterCriticalServiceAfter,
                'HTTP' => "http://{$host}:{$port}/",
                'Interval' => $interval,
            ];
        }
        if (in_array($protocol, ['jsonrpc', 'jsonrpc-tcp-length-check', 'multiplex.default'], true)) {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => $deregisterCriticalServiceAfter,
                'TCP' => "{$host}:{$port}",
                'Interval' => $interval,
            ];
        }
        $response = $this->client()->registerService($requestBody);
        if ($response->getStatusCode() === 200) {
            $this->logger->info(sprintf('Service %s:%s register to the consul successfully.', $name, $nextId));
        } else {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name));
        }
    }

    public function isRegistered(string $name, string $address, int $port, array $metadata): bool
    {
        $protocol = $metadata['protocol'];
        $client = $this->client();
        $response = $client->services();
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name));
            return false;
        }
        $services = $response->json();
        $glue = ',';
        $tag = implode($glue, [$name, $address, $port, $protocol]);
        foreach ($services as $serviceId => $service) {
            if (! isset($service['Service'], $service['Address'], $service['Port'], $service['Meta']['Protocol'])) {
                continue;
            }
            $currentTag = implode($glue, [
                $service['Service'],
                $service['Address'],
                $service['Port'],
                $service['Meta']['Protocol'],
            ]);
            if ($currentTag === $tag) {
                return true;
            }
        }
        return false;
    }
}
