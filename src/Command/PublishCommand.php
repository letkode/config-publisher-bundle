<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisher\Command;

use Letkode\ConfigPublisher\Publishable;
use Letkode\ConfigPublisher\PublishableDiscovery;
use Letkode\ConfigPublisher\Publisher;
use Letkode\ConfigPublisher\PublishResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: self::NAME, description: 'Copies the example config files of installed letkode/* packages into the project')]
final class PublishCommand extends Command
{
    public const string NAME = 'publish';

    public function __construct(
        private readonly PublishableDiscovery $discovery,
        private readonly Publisher $publisher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('packages', InputArgument::IS_ARRAY, 'Packages to publish: short name ("locale") or full name ("letkode/locale-bundle")')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Publish every installed package that offers files')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite files that already exist')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be copied without writing anything')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $available = $this->discovery->discover();

        if ([] === $available) {
            $io->warning('No installed package offers files to publish.');

            return Command::SUCCESS;
        }

        /** @var list<string> $requested */
        $requested = $input->getArgument('packages');

        if ($input->getOption('all') && [] !== $requested) {
            $io->error('Use either package names or --all, not both.');

            return Command::INVALID;
        }

        $names = $this->namesOf($available);

        if ($input->getOption('all')) {
            $selected = $available;
        } else {
            if ([] === $requested) {
                if (!$input->isInteractive()) {
                    $io->error('Name the packages to publish, or use --all.');
                    $io->listing($names);

                    return Command::INVALID;
                }

                /** @var list<string> $requested */
                $requested = (array) $io->askQuestion(new ChoiceQuestion('Which packages do you want to publish? (comma-separated)', $names)->setMultiselect(true));
            }

            $selected = $this->select($available, $requested, $io);

            if (null === $selected) {
                return Command::FAILURE;
            }
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        $skipped = 0;

        foreach ($selected as $publishable) {
            $result = $this->publisher->publish($publishable, $force, $dryRun);
            $skipped += PublishResult::Skipped === $result ? 1 : 0;
            $io->writeln($this->line($publishable, $result, $dryRun));
        }

        if ($skipped > 0) {
            $io->newLine();
            $io->note('Existing files were kept. Use --force to overwrite them.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<Publishable> $available
     * @param list<string>      $requested
     *
     * @return list<Publishable>|null null when a name is unknown or ambiguous (already reported)
     */
    private function select(array $available, array $requested, SymfonyStyle $io): array|null
    {
        $selected = [];

        foreach (array_unique($requested) as $name) {
            $matches = array_values(array_filter(
                $available,
                static fn (Publishable $p): bool => $p->package === $name || $p->name === $name,
            ));

            if ([] === $matches) {
                $io->error(\sprintf('"%s" is not installed or offers no files. Available:', $name));
                $io->listing($this->namesOf($available));

                return null;
            }

            if (\count(array_unique(array_map(static fn (Publishable $p): string => $p->package, $matches))) > 1) {
                $io->error(\sprintf('"%s" is ambiguous. Use the full package name.', $name));

                return null;
            }

            array_push($selected, ...$matches);
        }

        return $selected;
    }

    /**
     * @param list<Publishable> $available
     *
     * @return list<string>
     */
    private function namesOf(array $available): array
    {
        $names = array_values(array_unique(array_map(static fn (Publishable $p): string => $p->name, $available)));
        sort($names);

        return $names;
    }

    private function line(Publishable $publishable, PublishResult $result, bool $dryRun): string
    {
        $label = match ($result) {
            PublishResult::Created => $dryRun ? 'would create   ' : 'created        ',
            PublishResult::Overwritten => $dryRun ? 'would overwrite' : 'overwritten    ',
            PublishResult::Skipped => 'skipped (exists)',
        };

        return \sprintf(' %s %s  <fg=gray>(%s)</>', $label, $publishable->destination, $publishable->package);
    }
}
