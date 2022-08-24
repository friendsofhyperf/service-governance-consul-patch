<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-consul-patch.
 *
 * @link     https://github.com/8591/friendsofhyperf/service-governance-consul-patch
 * @document https://github.com/8591/friendsofhyperf/service-governance-consul-patch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceConsulPatch\Command;

use FriendsOfHyperf\ServiceGovernanceConsulPatch\ConsulHealth;
use Hyperf\Command\Annotation\Command;
use Hyperf\ServiceGovernanceConsul\ConsulAgent;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class DeregisterCommand extends \Hyperf\Command\Command
{
    /**
     * @var string
     */
    protected $signature = 'consul:deregister {service? : Service Name}';

    /**
     * @var ConsulAgent
     */
    protected $consulAgent;

    /**
     * @var ConsulHealth
     */
    protected $consulHealth;

    public function __construct(ContainerInterface $container)
    {
        $this->consulAgent = $container->get(ConsulAgent::class);
        $this->consulHealth = $container->get(ConsulHealth::class);

        parent::__construct();
    }

    public function handle()
    {
        $serviceName = $this->input->getArgument('service');

        if (! $serviceName) {
            $serviceNames = collect($this->consulAgent->services()->json())
                ->transform(fn ($item) => $item['Service'])
                ->values()
                ->unique()
                ->all();
            $serviceName = $this->choice('ServiceName', $serviceNames);
        }

        $serviceIds = collect($this->consulHealth->service($serviceName)->json())
            ->tap(function ($services) {
                $this->info(json_encode($services, JSON_PRETTY_PRINT), 'v');
            })
            ->transform(fn ($item) => sprintf(
                '%s [%s]',
                $item['Service']['ID'],
                $item['Checks'][1]['Status'] ?? $item['Checks'][0]['Status'] ?? 'unknown'
            ))
            ->unique()
            ->all();
        $serviceId = $this->choice('ServiceID', $serviceIds);
        [$serviceId] = explode(' ', $serviceId);

        $this->info($serviceId, 'v');

        $response = $this->consulAgent->deregisterService($serviceId);

        if ($response->getReasonPhrase() == 'OK') {
            $this->info($serviceId . ' deregister success.');
        } else {
            $this->warn($serviceId . ' deregister failed.');
        }
    }
}
