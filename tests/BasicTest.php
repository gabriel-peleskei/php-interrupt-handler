<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Gabriel Peleskei
	 * Date: 02.12.2018
	 */
	
	namespace GabrielPeleskei\InterruptHandler\Test;
	
	
	use GabrielPeleskei\InterruptHandler\Handler;
	use GabrielPeleskei\InterruptHandler\Listener;
	use function in_array;
	use InvalidArgumentException;
	use PHPUnit\Framework\TestCase;
	use const SIGINT;
	use const SIGKILL;
	use const SIGTERM;
	
	class BasicTest
		extends TestCase {
		
		public function testBasicInit() {
			$handler = Handler::getInstance();
			$initList = $handler->listSignals();
			$this->assertInstanceOf(Handler::class, $handler,
			                        'getInstance() returns wrong class!');
			$this->assertContains(SIGINT,$handler->listSignals(),
			                      'SIGINT should be in default signal list');
			$this->assertNotContains(SIGKILL,$handler->listSignals(),
			                         'SIGKILL should not be in signal list');
			$handler = Handler::getInstance([SIGTERM]);
			$this->assertInstanceOf(Handler::class,$handler,
				'getInstance() returns wrong class!');
			$this->assertContains(SIGTERM,$handler->listSignals(),
				'SIGTERM should be in signal list');
			$this->assertNotContains(SIGINT,$handler->listSignals(),
				'SIGINT should not be in signal list');
			$handler->cleanup();
			$handler = Handler::getInstance();
			$this->assertArraySubset($handler->listSignals(), $initList,true,
				'After Cleanup: singal list should be equal');
			
			$this->assertArraySubset($initList, $handler->listSignals(),true,
			                         'After Cleanup (reverse): singal list should be equal');
			$handler->cleanup();
		}
		
		/**
		 * @expectedException InvalidArgumentException
		 */
		public function testRegisterException() {
			$handler = Handler::getInstance([SIGTERM]);
			$handler->register([SIGINT]);
		}
		
		public function testListenerRegistration() {
			Handler::getInstance()->cleanup();
			$handler = Handler::getInstance();
			$listener = $handler->register([SIGINT]);
			$this->assertInstanceOf(Listener::class,$listener,
				'Handler::register() should return an instance of Listener');
			$value = -1;
			for($i = 0; $i < 10; $i++) {
				if ( $listener->interrupt) {
					$value = $i;
					break;
				}
				if ( $i === 3) {
					posix_kill(posix_getpid(), SIGINT);
				}
			}
			$this->assertEquals(4, $value,
			                    "Signal has not been caught!");
		}
		
		public function testRepeatedInterruption() {
			$handler = Handler::getInstance();
			$listener = $handler->register([SIGINT]);
			$listener->resetAfter(true);
			$value = -1;
			for ($i = 0; $i < 10; $i++) {
				if ( $listener->interrupt ) {
					$this->assertEquals($value,$i,
						'SIGINT iteration does not match!');
				}
				
				if ( in_array($i, [1,3,8]) ) {
					$value = $i+1;
					posix_kill(posix_getpid(), SIGINT);
				}
			}
			$handler->cleanup();
		}
		
		public function testCallable() {
			$handler = Handler::getInstance();
			$value = -1;
			$asserts = 0;
			$handler->register([SIGINT], function() use(&$value, &$asserts) {
				declare(ticks=1); // important
				$this->assertEquals(-1, $value,
					'initial value is wrong!');
				$value = 9;
				$asserts++;
			});
			$handler->register([SIGINT], function() use(&$value, &$asserts) {
				$this->assertEquals(9, $value,
					'Value should be 9 by previous interrupt');
				$asserts++;
			});
			posix_kill(posix_getpid(), SIGINT);
			$this->assertEquals(9, $value,
				'New value should be 9');
			$this->assertEquals(2,$asserts,
				'Only 1 callback should have been processed');
			$handler->cleanup();
		}
	}