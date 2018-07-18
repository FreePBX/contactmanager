<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Command\HelpCommand;
class Contactmanager extends Command {
	protected function configure(){
		$this->setName('contactmanager')
		->setDescription(_('Contact Manager'))
		->setDefinition(array(
			new InputOption('lookup', null, InputOption::VALUE_REQUIRED, _('Lookup number and get a contact back as a result')),
			new InputOption('uid', null, InputOption::VALUE_REQUIRED, _('Lookup backed on User Manager Users View'))
		));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		if($input->getOption('lookup')){
				$id = $input->getOption('uid') ? $input->getOption('uid') : -1;
				$info = \FreePBX::Contactmanager()->lookupNumberByUserID($id, $input->getOption('lookup'));
				$output->writeln(print_r($info,true));
			return;
		}
		$this->outputHelp($input,$output);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Symfony\Component\Console\Exception\ExceptionInterface
	 */
	protected function outputHelp(InputInterface $input, OutputInterface $output)	 {
		$help = new HelpCommand();
		$help->setCommand($this);
		return $help->run($input, $output);
	}
}
