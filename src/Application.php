<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

use Letkode\ConfigPublisherBundle\Command\PublishCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Single-command application: `letkode-publish locale` instead of `letkode-publish publish locale`.
 */
final class Application extends ConsoleApplication
{
    public function __construct(private readonly PublishCommand $command)
    {
        parent::__construct('letkode-publish');
    }

    protected function getCommandName(InputInterface $input): string
    {
        return PublishCommand::NAME;
    }

    /**
     * @return Command[]
     */
    protected function getDefaultCommands(): array
    {
        return [...parent::getDefaultCommands(), $this->command];
    }

    public function getDefinition(): InputDefinition
    {
        $definition = parent::getDefinition();
        $definition->setArguments();

        return $definition;
    }
}
