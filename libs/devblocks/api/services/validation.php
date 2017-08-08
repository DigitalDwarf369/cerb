<?php
class Exception_DevblocksValidationError extends Exception_Devblocks {};

class _DevblocksValidationField {
	public $_name = null;
	public $_type = null;
	
	function __construct($name) {
		$this->_name = $name;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeContext
	 */
	function context() {
		$this->_type = new _DevblocksValidationTypeContext();
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeNumber
	 */
	function id() {
		$this->_type = new _DevblocksValidationTypeNumber();
		
		// Defaults for id type
		return $this->_type
			->setMin(0)
			->setMax(pow(2,32))
			;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeNumber
	 */
	function number() {
		$this->_type = new _DevblocksValidationTypeNumber();
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeString
	 */
	function string() {
		$this->_type = new _DevblocksValidationTypeString();
		return $this->_type;
	}
	
	/**
	 * 
	 * @return DevblocksValidationTypeNumber
	 */
	function timestamp() {
		$this->_type = new _DevblocksValidationTypeNumber();
		return $this->_type
			->setMin(0)
			->setMax(pow(2,32)) // 4 unsigned bytes
			;
		;
	}
}

class _DevblocksValidationType {
	public $_data = [
		'editable' => true,
	];
	
	function __construct() {
		return $this;
	}
	
	function setEditable($bool) {
		$this->_data['editable'] = $bool ? true : false;
	}
}

class _DevblocksValidationTypeContext extends _DevblocksValidationType {
	
}

class _DevblocksValidationTypeNumber extends _DevblocksValidationType {
	function __construct() {
		parent::__construct();
		return $this;
	}
	
	function setMin($n) {
		if(!is_numeric($n))
			return false;
		
		$this->_data['min'] = intval($n);
		return $this;
	}
	
	function setMax($n) {
		if(!is_numeric($n))
			return false;
		
		$this->_data['max'] = intval($n);
		return $this;
	}
}

class _DevblocksValidationTypeString extends _DevblocksValidationType {
	// [TODO] JSON formatter
	// [TODO] formatter vs validator
	function addFormatter($callable) {
		// [TODO] Throw?
		if(!is_callable($callable))
			return false;
		
		if(!isset($this->_data['formatters']))
			$this->_data['formatters'] = [];
		
		$this->_data['formatters'][] = $callable;
		return $this;
	}
	
	function setMaxLength($length) {
		$this->_data['length'] = intval($length);
		return $this;
	}
	
	function setNotEmpty($bool) {
		$this->_data['not_empty'] = $bool ? true : false;
		return $this;
	}
	
	function setUnique($bool, $dao_class) {
		$this->_data['unique'] = $bool ? true : false;
		$this->_data['dao_class'] = $dao_class;
		return $this;
	}
	
	function setPossibleValues(array $possible_values) {
		$this->_data['possible_values'] = $possible_values;
		return $this;
	}
}

class _DevblocksValidationService {
	private $_fields = [];
	
	/**
	 * 
	 * @param string $name
	 * @return _DevblocksValidationField
	 */
	function addField($name) {
		$this->_fields[$name] = new _DevblocksValidationField($name);
		return $this->_fields[$name];
	}
	
	/*
	 * @return _DevblocksValidationField[]
	 */
	function getFields() {
		return $this->_fields;
	}
	
	/*
	function getFormatter() {
		return new _DevblocksValidationField();
	}
	*/
	
	// (ip, email, phone, etc)
	function validate(_DevblocksValidationField $field, $value, $scope=[]) {
		$field_name = $field->_name;
		
		if(false == ($class_name = get_class($field->_type)))
			throw new Exception_DevblocksValidationError("'%s' has an invalid type.", $field_name);
		
		$data = $field->_type->_data;
		
		if(isset($data['editable'])) {
			if(!$data['editable'])
				throw new Exception_DevblocksValidationError(sprintf("'%s' is not editable.", $field_name));
		}
		
		switch($class_name) {
			case '_DevblocksValidationTypeContext':
				if(!is_string($value) || false == ($context_ext = Extension_DevblocksContext::get($value))) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' is not a valid context (%s).", $field_name, $value));
				}
				// [TODO] Filter to specific contexts for certain fields
				break;
				
			case '_DevblocksValidationTypeId':
			case '_DevblocksValidationTypeNumber':
				if(!is_numeric($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a number (%s: %s).", $field_name, gettype($value), $value));
				}
				
				if($data) {
					if(isset($data['min']) && $value < $data['min']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be >= %d (%d)", $field_name, $data['min'], $value));
					}
					
					if(isset($data['max']) && $value > $data['max']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be <= %d (%d)", $field_name, $data['max'], $value));
					}
				}
				break;
				
			case '_DevblocksValidationTypeString':
				if(!is_string($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a string (%s).", $field_name, gettype($value)));
				}
				
				if($data) {
					if(isset($data['length']) && strlen($value) > $data['length']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be no longer than %d characters.", $field_name, $data['length']));
					}
					
					if(isset($data['not_empty']) && $data['not_empty'] && 0 == strlen($value)) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must not be blank.", $field_name));
					}
					
					// [TODO] This would have trouble if we were bulk updating a unique field
					if(isset($data['unique']) && $data['unique']) {
						@$dao_class = $data['dao_class'];
						
						if(empty($dao_class))
							throw new Exception_DevblocksValidationError("'%s' has an invalid unique constraint.", $field_name);
						
						if(isset($scope['id'])) {
							$results = $dao_class::getWhere(sprintf("%s = %s AND id != %d", $dao_class::escape($field_name), $dao_class::qstr($value), $scope['id']), null, null, 1);
						} else {
							$results = $dao_class::getWhere(sprintf("%s = %s", $dao_class::escape($field_name), $dao_class::qstr($value)), null, null, 1);
						}
						
						if(!empty($results)) {
							throw new Exception_DevblocksValidationError(sprintf("'%s' must be unique (%s).", $field_name, $value));
						}
					}
					
					if(isset($data['possible_values']) && !in_array($value, $data['possible_values'])) {
						// [TODO] Handle multiple values
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be one of: %s", $field_name, implode(', ', $data['possible_values'])));
					}
					
					if(isset($data['formatters']) && is_array($data['formatters']))
					foreach($data['formatters'] as $formatter) {
						if(!is_callable($formatter)) {
							throw new Exception_DevblocksValidationError(sprintf("'%s' has an invalid formatter.", $field_name));
						}
						
						if(!$formatter($value, $error)) {
							throw new Exception_DevblocksValidationError(sprintf("'%s' %s", $field_name, $error));
						}
					}
				}
				break;
		}
			
		return true;
	}
};