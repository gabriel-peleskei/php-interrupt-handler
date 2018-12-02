<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Gabriel Peleskei
	 * Date: 25.11.2018
	 */
	
	use GabrielPeleskei\InterruptHandler\Handler;
	
	require __DIR__ . '/../vendor/autoload.php';
	$intHandler = Handler::getInstance();
	
	$listener = $intHandler->register([SIGINT, SIGHUP]);
	$listener->resetAfter(true);
	$listener->name('signint-handler');
	$listener2 = $intHandler->register([SIGTERM]);
	$listener2->resetAfter(true);
	$listener2->name('sigterm-handler');
	
	echo "start looping..\n";
	for($i=1;;$i++) {
		if ( $listener2->interrupt ) {
			echo "Interruption (TERM = $listener2) received. Time to leave loop.\n";
			break;
		}
		echo "$i - Running in loop\n";
		sleep(1);
	}
	echo "start looping2..\n";
	for($i=1;;$i++) {
		if ( $listener->interrupt ) {
			echo "Interruption received (INT/HUP = $listener). Time to leave loop.\n";
			break;
		}
		echo "$i - Running in loop\n";
		sleep(1);
	}
	echo "looop finished...\n";
	exit;