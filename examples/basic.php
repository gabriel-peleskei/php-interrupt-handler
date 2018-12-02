<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Gabriel Peleskei
	 * Date: 25.11.2018
	 *
	 */
	
	use GabrielPeleskei\InterruptHandler\Handler;
	
	require __DIR__ . '/../vendor/autoload.php';
	$intHandler = Handler::getInstance();
	
	$listener = $intHandler->register([SIGINT, SIGTERM]);
	
	echo "$intHandler\nstart looping..\n";
	for($i=1; $i<11; $i++) {
		if ( $listener->interrupt ) {
			echo "Interruption ($listener) received. Time to leave loop.\n";
			break;
		}
		echo "$i - Running in loop\n";
		sleep(1);
	}
	
	echo "looop finished...\n";
	exit;