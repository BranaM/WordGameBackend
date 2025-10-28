<?php
namespace App\Command;

use App\Service\WordService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ScoreWordCommand extends Command
{
    private $wordService;

    public function __construct(WordService $wordService)
    {
        parent::__construct();
        $this->wordService = $wordService;
    }

    protected function configure(): void
    {
        $this->setName('app:score-word')
            ->setDescription('Console app for WordGame app!.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $output->writeln("Welcome to WordGame console!");
        $output->writeln("Type '!exit' to leave.");

        while (true) {
            $question = new Question('Enter your word: ');
            $word = $helper->ask($input, $output, $question);

            if (strtolower($word) === '!exit') {
                $output->writeln("Exiting application. Thank you for playing!");
                break;
            }

            $result = $this->wordService->processWord($word);

            if (!$result->isValid) {
                $output->writeln($result->message . "\n");
                continue;
            }

            $output->writeln("Score for word: '$word' is: {$result->score}\n");
        }
        return Command::SUCCESS;
    }
}


