<?php

declare(strict_types=1);
/**
 * This file is part of reporting.
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
    protected $signature = 'service:deregister {service : "Service name"}';

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
            $this->error('Invalid argument [id]');
            return;
        }

        $choices = collect($this->consulHealth->service($serviceName)->json())
            ->transform(function ($item) {
                return [
                    $item['Service']['ID'] => sprintf('%s [%s]', $item['Service']['ID'], ''),
                ];
            })
            ->unique()
            ->all();

        $serviceId = $this->choice('Choice service id to deregister:', $choices);

        $this->info('Your choice:' . $serviceId);

        // $this->consulAgent->deregisterService($serviceName);
    }
}
