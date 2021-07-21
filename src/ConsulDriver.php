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

class ConsulDriver extends \Hyperf\ServiceGovernanceConsul\ConsulDriver
{
    public function register(string $name, string $host, int $port, array $metadata): void
    {
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
                'DeregisterCriticalServiceAfter' => '10s',
                'HTTP' => "http://{$host}:{$port}/",
                'Interval' => '1s',
            ];
        }
        if (in_array($protocol, ['jsonrpc', 'jsonrpc-tcp-length-check'], true)) {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => '10s',
                'TCP' => "{$host}:{$port}",
                'Interval' => '1s',
            ];
        }
        $response = $this->client()->registerService($requestBody);
        if ($response->getStatusCode() === 200) {
            $this->registeredServices[$name][$protocol][$host][$port] = true;
            $this->logger->info(sprintf('Service %s:%s register to the consul successfully.', $name, $nextId));
        } else {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name));
        }
    }
}
