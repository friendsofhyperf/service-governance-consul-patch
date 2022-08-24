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

class ConsulDriver extends \Hyperf\ServiceGovernanceConsul\ConsulDriver
{
    public function getNodes(string $uri, string $name, array $metadata): array
    {
        return retry(5, fn () => parent::getNodes($uri, $name, $metadata), 500);
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
