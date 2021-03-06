<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Object\Linky;
use AppBundle\Entity\DataValue;
use AppBundle\Object\MeteoFrance;

/**
 * Defined command to refresh all feeds
 * @todo Simplify, no need for callbacks, just make Linky and MeteoFrance implements a same interface
 *
 */
class FetchDataCommand extends ContainerAwareCommand
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
        // the name of the command (the part after "bin/console").
        ->setName('pilea:fetch-data')

        // the short description shown while running "php bin/console list".
        ->setDescription('Get daily data from all feed')

        // the full command description shown when running the command with
        // the "--help" option.
        ->setHelp('This command allows you to fetch yesterday data for all defined feeds')

        // argument to know if we want to force refresh.
        ->addArgument('force', InputArgument::REQUIRED, 'Refresh data for $date even if it already exists ?')

        // argument to know if we want to force refresh.
        ->addArgument('date', InputArgument::OPTIONAL, 'The date we want to fetch data format Y-m-d, if not given, fetch data for yesterday.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $force = filter_var($input->getArgument('force'), FILTER_VALIDATE_BOOLEAN);

        // We fetch all Feeds data.
        $feeds = $this->entityManager->getRepository('AppBundle:Feed')->findAll();

        // If a date is given, we update only for this date.
        if($date=$input->getArgument('date')) {
            $date = new \DateTime($date);
            // For each feeds, we call the right method to fetch data.
            /** @var \AppBundle\Entity\Feed $feeds */
            foreach($feeds as $feed) {
                $feed->fetchDataForDate($this->entityManager, $date, $force);
            }
        }
        // Else we update from last data to yesterday.
        else {
            // Get yesterday datetime.
            $date = new \DateTime();
            $date->sub(new \DateInterval('P1D'));
            $date = new \DateTime($date->format("Y-m-d 00:00:00"));

            // For each feeds, we call the right method to fetch data.
            /** @var \AppBundle\Entity\Feed $feeds */
            foreach($feeds as $feed) {
                $feed->fetchDataToDate($this->entityManager, $date);
            }
        }
    }
}
