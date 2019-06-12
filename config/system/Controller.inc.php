<?php 
/**
* Controller base class
*/

class dsController
{
	function __construct()
	{
        // Set filename is stated
		dsCore::set_controller();
	}

	public function property_modify_controller($_property, $_value = NULL){
		$this->{$_property} = $_value;
	}
	private function add_object($_class, $_alias = NULL, $type)
	{
		if(is_null($_alias))
			$_alias = $_class;
		Load::$type($_class, $_alias);
		$this->property_modify_controller($_alias, _get($_alias));
	}
	public function add_model($_model, $_alias = NULL)
	{
		$this->add_object($_model, $_alias, Key::MODEL);
	}
	public function add_controller($_controller, $_alias = NULL)
	{
		$this->add_object($_controller, $_alias, Key::CONTROLLER);
	}
	public function add_library($_library, $_alias = NULL)
	{
		$this->add_object($_library, $_alias, Key::LIBRARY);
	}
	public function add_module($_module, $_alias = NULL)
	{
		$this->add_object($_module, $_alias, Key::MODULE);
	}
	// More Feature for Controller Here
	// ...
}