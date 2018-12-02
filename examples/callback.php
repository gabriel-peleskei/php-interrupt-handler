<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Gabriel Peleskei
	 * Date: 25.11.2018
	 */
	
	use GabrielPeleskei\InterruptHandler\Handler;
	
	require __DIR__ . '/../vendor/autoload.php';
	$intHandler = Handler::getInstance();
	$int = false;
	
	// Important when used with callbacks,
	// set it global or within the callback itself
	declare(ticks=1);
	
	$reg = $intHandler->register([SIGINT, SIGHUP], function($signal){
		global $int;
		$signalName = Handler::getSignalName($signal);
		echo "Signal ($signalName) reveived...\n";
		$int = true;
	});
	
	echo "$intHandler\nstart looping..\n";
	for($i=1; $i<11; $i++) {
		if ( $int ) {
			echo "Interruption received (set by callback). Time to leave loop.\n";
			$reg->reset(); // make it reusable
			break;
		}
		echo "$i - Running in loop\n";
		sleep(1);
		if ($i === 5) {
			echo "Trigger SIGHUP\n";
			posix_kill(posix_getpid(), SIGHUP);
		}
	}
	
	
	echo "$reg\nstart looping with state..\n";
	for($i=1; $i<11; $i++) {
		if ( $reg->interrupt ) {
			echo "Interruption ($reg) received. Time to leave loop.\n";
			break;
		}
		echo "$i - Running in loop\n";
		sleep(1);
		if ($i === 2) {
			echo "Trigger SIGINT\n";
			posix_kill(posix_getpid(), SIGINT);
		}
	}
	
	echo "looop finished...\n";
	exit;