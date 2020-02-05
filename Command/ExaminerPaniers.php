<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace PaniersAbandonnes\Command;

use PaniersAbandonnes\Events\PaniersAbandonnesEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Core\Event\DefaultActionEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;

class ExaminerPaniers extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("examiner-paniers-abandonnes")
            ->setDescription("Examine les paniers abandonnes en evoie les mails de rappel si nécessaire.")
        ;
    }

    protected function init()
    {
        $container = $this->getContainer();

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');
        $requestStack->push($request);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        // histoire d'avoir les bonnes urls dans le mail et pas 127.0.0.1 ;)
        $this->initRequest();

        try {
            $this->getDispatcher()->dispatch(PaniersAbandonnesEvent::EXAMINER_PANIERS_EVENT, new DefaultActionEvent());

        } catch (\Exception $ex) {
            $output->writeln(
                "<error>".$ex->getMessage()."</error>"
            );
            $output->writeln(
                "<error>".$ex->getTraceAsString()."</error>"
            );
        }
    }
}
