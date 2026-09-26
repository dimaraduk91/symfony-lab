<?php
declare(strict_types=1);
namespace App\Ordering\Presentation\Console;
use App\Ordering\Application\Outbox\OutboxPublisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: 'app:outbox:publish')]
final class PublishOutboxMessagesCommand extends Command
{
    public function __construct(private readonly OutboxPublisher $publisher) { parent::__construct(); }
    protected function configure(): void { $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum messages.', 100); }
    protected function execute(InputInterface $input, OutputInterface $output): int { $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]); if ($limit === false) { return Command::INVALID; } $output->writeln(sprintf('Published %d outbox message(s).', $this->publisher->publish($limit))); return Command::SUCCESS; }
}
