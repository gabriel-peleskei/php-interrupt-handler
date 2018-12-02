<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Gabriel Peleskei
	 * Date: 25.11.2018
	 */
	
	namespace GabrielPeleskei\InterruptHandler;
	
	use function call_user_func_array;
	use Closure;
	use function is_array;
	use function is_callable;
	use function str_replace;
	use function uniqid;
	
	/**
	 * Class Listener
	 *
	 * Is returned by {@see Handler::register()}
	 *
	 * @package GabrielPeleskei\InterruptHandler
	 *
	 * @property bool $interrupt
	 * @property-read string $name
	 * @property-set  string|null $name
	 * @property-read string $id
	 * @property-read array $signals
	 * @method string name($name=null)
	 */
	class Listener {
		/**
		 * @var string generated id
		 */
		protected $_id;
		/**
		 * @var array Signals to register against
		 */
		protected $_signals   = [];
		/**
		 * @var int current interrupt, 0 none
		 */
		protected $_interrupt = 0;
		/**
		 * @var Callable|Closure|array method/function to call on interruption
		 */
		protected $_caller;
		/**
		 * @var bool
		 */
		protected $_resetAfter = false;
		/**
		 * @var string|null
		 */
		protected $_name;
		
		/**
		 * Listener constructor.
		 * @param array $signals singals to register against
		 */
		public function __construct(array $signals) {
			$this->_id      = str_replace('.', '', uniqid('sig_lst_', true));
			$this->_signals = $signals;
		}
		
		/**
		 * @param int $signal test if signal is part of this class
		 * @return bool true if registered
		 */
		public function hasSignal($signal) {
			return in_array($signal, $this->_signals, true);
		}
		
		/**
		 * @param null|bool $shouldReset if null return current value, else sets value beforehand
		 * @return bool
		 */
		public function resetAfter($shouldReset = null) {
			if (null !== $shouldReset) {
				$this->_resetAfter = true;
			}
			return $this->_resetAfter;
		}
		
		
		/**
		 * @param Callable|Closure|array $callableOrArray method/function to call on interruption
		 */
		public function setNotification($callableOrArray) {
			if (is_callable($callableOrArray) || is_array($callableOrArray) || ($callableOrArray instanceof Closure)) {
				$this->_caller = $callableOrArray;
			}
		}
		
		/**
		 * resets the interrupt signal
		 */
		public function reset() {
			$this->_interrupt = 0;
		}
		
		/**
		 * Called on interruption
		 * @return null|int
		 */
		public function notify() {
			declare(ticks=1);
			if (is_array($this->_caller) && !empty($this->_caller)) {
				// array
				return call_user_func_array($this->_caller, [$this->_interrupt]);
			} else if ($this->_caller instanceof Closure) {
				// closure
				return $this->_caller->call($this, $this->_interrupt);
			} else if (is_callable($this->_caller)) {
				// callable
				$cl = Closure::fromCallable($this->_caller);
				return $cl->call($this,$this->_interrupt);
			}
			return null;
		}
		
		/**
		 * Magic access
		 * @param string $name
		 * @return array|bool|null|string
		 */
		public function __get($name) {
			// interrupt state
			if ($name === 'interrupt') {
				declare(ticks=1);
				$return = (bool)$this->_interrupt;
				if ($this->_resetAfter) {
					$this->reset();
				}
				return $return;
			}
			// id
			if ($name === 'id') {
				return $this->_id;
			}
			// list of signals (registered)
			if ($name === 'signals') {
				return $this->_signals;
			}
			if ( $name === 'name') {
				return $this->name();
			}
			return null;
		}
		
		/**
		 * Magic setter
		 * @param string $name
		 * @param mixed $value
		 */
		public function __set($name, $value) {
			if ($name === 'interrupt' && $this->hasSignal($value)) {
				// set interrupt signal and notify
				$this->_interrupt = (int)$value;
				$this->notify();
			} else if ($name === 'name') {
				$this->name($value);
			}
		}
		
		/**
		 * @param $name
		 * @param $arguments
		 * @return string
		 */
		public function __call($name, $arguments) {
			if ($name === 'name') {
				if ( isset($arguments[0]) && null !== $arguments[0]) {
					$this->_name = $arguments[0];
				}
				return $this->_name ?: $this->_id;
			}
		}
		
		/**
		 * @return string
		 * @see Listener::$name
		 */
		public function __toString() {
			return $this->name;
		}
	}