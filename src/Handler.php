<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Gabriel Peleskei
	 * Date: 25.11.2018
	 */
	
	namespace GabrielPeleskei\InterruptHandler;
	
	use Closure;
	use function in_array;
	use InvalidArgumentException;
	use function pcntl_signal;
	use const SIG_DFL;
	use const SIGHUP;
	use const SIGINT;
	use const SIGUSR1;
	
	/**
	 * Class Handler
	 * @package GabrielPeleskei\InterruptHandler
	 *
	 * Singleton implementation
	 * @see Handler::getInstance()
	 * @example ../examples/basic.php
	 */
	class Handler {
		static public $signalNames = [
			'SIG_DFL',
			'SIGHUP',
			'SIGINT',
			'SIGQUIT',
			'SIGILL',
			'SIGTRAP',
			'SIGABRT',
			'SIGIOT',
			'SIGBUS',
			'SIGFPE',
			'SIGKILL',
			'SIGUSR1',
			'SIGSEGV',
			'SIGUSR2',
			'SIGPIPE',
			'SIGALRM',
			'SIGTERM',
			'SIGSTKFLT',
			'SIGCLD',
			'SIGCHLD',
			'SIGCONT',
			'SIGSTOP',
			'SIGTSTP',
			'SIGTTIN',
			'SIGTTOU',
			'SIGURG',
			'SIGXCPU',
			'SIGXFSZ',
			'SIGVTALRM',
			'SIGPROF',
			'SIGWINCH',
			'SIGPOLL',
			'SIGIO',
			'SIGPWR',
			'SIGSYS',
			'SIGBABY'
		];
		/**
		 * @var Handler
		 */
		protected static $_instance;
		
		/**
		 * @var array Listener class hash by signal
		 */
		protected $_bySignal = [];
		/**
		 * @var array Listener class hash bei register id
		 */
		protected $_byId = [];
		/**
		 * @var array list of signals registered to pcntl
		 */
		protected $_signals = [
			SIGINT,
			SIGHUP,
			SIGUSR1,
			SIGTERM
		];
		
		/**
		 * Handler constructor.
		 * starts the interrupt handler
		 * @uses Handler::_start()
		 */
		protected function __construct() {
			$this->_start();
			static::$_instance = $this;
		}
		
		/**
		 * @param array|null $supportedSignals
		 * @return static
		 */
		public static function getInstance(array $supportedSignals = null) {
			$instance = self::$_instance ?: new static();
			if (null !== $supportedSignals) {
				$instance->setSignalsArray($supportedSignals);
			}
			return $instance;
		}
		
		/**
		 * Resets interrupt handlers to default...
		 * @uses pcntl_signal()
		 */
		protected function _reset() {
			declare(ticks=1);
			foreach ($this->_signals as $signal) {
				pcntl_signal($signal,SIG_DFL);
			}
		}
		
		/**
		 * @param array ...$signals
		 * @uses Handler::setSignalsArray()
		 */
		public function setSignals(...$signals) {
			$this->setSignalsArray($signals);
		}
		
		/**
		 * @return array of usable signals
		 */
		public function listSignals() {
			return $this->_signals;
		}
		
		/**
		 * @param array $signals
		 * @uses Handler::_reset()
		 * @uses Handler::_start()
		 */
		public function setSignalsArray(array $signals) {
			$this->_reset();
			$this->_signals = $signals;
			$this->_start();
		}
		
		/**
		 * Signal handler for all registrations.
		 *
		 * @param $signal
		 * @return int
		 */
		public function handle($signal) {
			if (isset($this->_bySignal[$signal])) {
				/** @var Listener $reg */
				foreach ($this->_bySignal[$signal] as $reg) {
					$reg->interrupt = $signal;
				}
			} else {
				return SIG_DFL;
			}
		}
		
		/**
		 * registeres the interrupt handler
		 * @uses pcntl_signal()
		 */
		protected function _start() {
			declare(ticks=1);
			foreach ($this->_signals as $signal) {
				pcntl_signal($signal, [$this, 'handle']);
			}
		}
		
		/**
		 * @param int $sig
		 * @return string|int mixed the number if no string is found
		 */
		public static function getSignalName($sig) {
			return isset(static::$signalNames[$sig]) ? static::$signalNames[$sig] : $sig;
		}
		
		/**
		 * Registers a new Interrupt Listener
		 *
		 * @param array $signals
		 * @param null|Callable|Closure|array $callableArray
		 *
		 * @return Listener
		 *
		 * @uses Listener::setNotification()
		 *
		 * @throws \InvalidArgumentException if signal ist not supported by Handler, code is the requested signal
		 *
		 * @example ../examples/basic.php
		 */
		public function register(array $signals, $callableArray = null) {
			foreach ($signals as $signal) {
				if (!in_array($signal, $this->_signals, true)) {
					$signalName = static::getSignalName($signal);
					throw new InvalidArgumentException("Signal [{$signalName}] is not supported. Use setSignals() to add support.", $signal);
				}
			}
			$reg = new Listener($signals);
			$reg->setNotification($callableArray);
			$this->_byId[$reg->id] = $reg;
			foreach ($signals as $signal) {
				$this->_bySignal[$signal][$reg->id] = $reg;
			}
			return $reg;
		}
		
		/**
		 * Clears the hash list of Listener
		 * @param Listener $register
		 * @return bool true if removed
		 */
		public function unregister(Listener $register) {
			$id = $register->id;
			$success = false;
			if (isset($this->_byId[$id])) {
				unset($this->_byId[$id]);
				$success = true;
			}
			foreach ($this->_bySignal as $signal => $reg) {
				if (isset($reg[$id])) {
					unset($this->_bySignal[$signal][$id]);
				}
			}
			return $success;
		}
		
		/**
		 * Unsets static singleton holder
		 * @uses Handler::_reset()
		 */
		public function cleanup() {
			$this->_reset();
			static::$_instance = null;
		}
		
		/**
		 * @return string
		 */
		public function __toString() {
			$sigs = array_map(function($sig){
				return static::getSignalName($sig) . "-$sig";
			}, $this->_signals);
			$sigs = implode(', ',$sigs);
			$listeners = count($this->_byId);
			return __CLASS__ . " ($listeners) [$sigs]";
		}
	}