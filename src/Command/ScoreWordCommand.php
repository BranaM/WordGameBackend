<?php
namespace App\Command;

use App\Service\WordService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ScoreWordCommand extends Command
{
    // protected static $defaultName = 'app:score-word';
    private $wordService;

    public function __construct(WordService $wordService)
    {
        parent::__construct();
        $this->wordService = $wordService;
    }

    protected function configure(): void
    {
        
        $this
            ->setName('app:score-word')
            ->setDescription('Omogucava korisniku da unese reci i dobije njihov score.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $output->writeln("Dobrodosli u WordGame konzolu!");
        $output->writeln("Upisite 'exit' da izadjete.");

        while (true) {
            $question = new Question('Unesite rec: ');
            $rec = $helper->ask($input, $output, $question);

            if (strtolower($rec) === 'exit') {
            $output->writeln("Izlaz iz aplikacije. Hvala sto ste igrali!");
            break;
            }

            if (!$this->wordService->isEnglishWord($rec)) {
                $output->writeln("Reč '$rec' nije validna engleska reč.\n");
                continue;
            }

            $score = $this->wordService->calculateScore($rec);
            $output->writeln("Score reci '$rec' je: $score\n");
        }
        return Command::SUCCESS;
    }
}


