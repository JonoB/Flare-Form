<?php

namespace Flare;

use \Form;
use \Input;
use \Session;
use \URI;

/**
 * Form generation based on Twitter Bootstrap with some added goodness.
 *
 * @package     Bundles
 * @subpackage  Forms
 * @author      JonoB
 * @version 	1.0.1
 *
 * @see http://github.com/JonoB/flare-formly
 * @see http://twitter.github.com/bootstrap/
 */
class Formly
{
	/**
	 * The default values for the form
	 */
	public $defaults = array();

	/**
	 * Default twitter form class
	 *
	 * Options are form-vertical, form-horizontal, form-inline, form-search
	 */
	public $form_class = 'form-horizontal';

	/**
	 * Automatically add a csrf token to the form_open method
	 */
	public $auto_token = true;

	/**
	 * Automatically create an id for each field based on the field name
	 */
	public $name_as_id = true;

	/**
	 * Prefix for field id or class
	 */
	public $id_prefix = 'field_';

	/**
	 * Text string to identify the required label
	 */
	public $required_label = '.req';

	/**
	 * Extra text added before the label for required fields
	 */
	public $required_prefix = '';

	/**
	 * Extra text added after the label for required fields
	 */
	public $required_suffix = ' *';

	/**
	 * Extra class added to the label for required fields
	 */
	public $required_class = 'label-required';

	/**
	 * Display a class for the control group if an input field fails validation
	 */
	public $control_group_error = 'error';

	/**
	 * Display inline validation error text
	 */
	public $display_inline_errors = false;

	/**
	 * Class constructor
	 *
	 * @param array $options
	 * @return void
	 */
	public function __construct($defaults = array())
	{
		$this->set_defaults($defaults);
	}

	/**
	 * Static function to instantiate the class
	 *
	 * @param  object $defaults
	 * @return class
	 */
	public static function make($defaults = '')
	{
	    $form = new Formly($defaults);
	    return $form;
	}

	/**
	 * Set the default options for the class
	 * @param array $defaults
	 */
	public function set_options($options = '')
	{
		if (count($options) > 0)
		{
			foreach ($options as $key => $value)
			{
				$this->$key = $value;
			}
		}
		return $this;
	}

	/**
	 * Set form defaults
	 *
	 * This would usually be done via the static make() function
	 *
	 * @param array $defaults
	 */
	public function set_defaults($defaults = '')
	{
		if (count($defaults) > 0)
		{
			$this->defaults = (object)$defaults;
		}
		return $this;
	}

	/**
	 * Overrides the base form open method to allow for automatic insertion of csrf tokens
	 * and form class
	 *
	 * @param  string $action     Defaults to the current uri
	 * @param  string $method     Defaults to POST
	 * @param  array  $attributes
	 * @param  bool $https
	 * @return string
	 */
	public function open($action = null, $method = 'POST', $attributes = array(), $https = null, $for_files = false)
	{
		// If an action has not been specified, use the current url
		$action = $action ?: URI::current();

		// Add in the form class if necessary
		if (empty($attributes['class']))
		{
			$attributes['class'] = $this->form_class;
		}
		elseif (strpos($attributes['class'], 'form-') === false)
		{
			$attributes['class'] .= ' ' . $this->form_class;
		}

		$out = Form::open($action, $method, $attributes, $https);
		if ($this->auto_token)
		{
			$out .= Form::token();
		}
		return $out;
	}

	public function open_for_files($action = null, $method = 'POST', $attributes = array(), $https = null)
	{
		$attributes['enctype'] = 'multipart/form-data';
		return $this->open($action, $method, $attributes, $https);
	}

	/**
	 * Create a HTML hidden input element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function hidden($name, $value = null, $attributes = array())
	{
		$value = $this->calculate_value($name, $value);
		return Form::input('hidden', $name, $value, $attributes);
	}

	/**
	 * Create a HTML text input element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function text($name, $label = '', $value = null, $attributes = array())
	{
		$value = $this->calculate_value($name, $value);
		$attributes = $this->set_attributes($name, $attributes);
		$field = Form::text($name, $value, $attributes);
		return $this->build_wrapper($field, $name, $label);
	}
	
	/**
	 * Create a HTML textarea input element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function textarea($name, $label = '', $value = null, $attributes = array())
	{
		$value = $this->calculate_value($name, $value);
		$attributes = $this->set_attributes($name, $attributes);
		if ( ! isset($attributes['rows']))
		{
			$attributes['rows'] = 4;
		}
		$field = Form::textarea($name, $value, $attributes);
		return $this->build_wrapper($field, $name, $label);
	}
	
	/**
	 * Create a HTML password input element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function password($name, $label = '', $attributes = array())
	{
		$attributes = $this->set_attributes($name, $attributes);
		$field = Form::password($name, $attributes);
		return $this->build_wrapper($field, $name, $label);
	}

	/**
	 * Create a HTML select element.
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  array   $options
	 * @param  string  $selected
	 * @param  array   $attributes
	 * @return string
	 */
	public function select($name, $label = '', $options = array(), $selected = null, $attributes = array())
	{
		$selected = $this->calculate_value($name, $selected);
		$attributes = $this->set_attributes($name, $attributes);
		$field = Form::select($name, $options, $selected, $attributes);
		return $this->build_wrapper($field, $name, $label);
	}

	/**
	 * Create a HTML checkbox input element.
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  string  $value
	 * @param  bool    $checked
	 * @param  array   $attributes
	 * @return string
	 */
	public function checkbox($name, $label = '', $value = 1, $checked = false, $attributes = array())
	{
		$checked = $this->calculate_value($name, $checked);
		$attributes = $this->set_attributes($name, $attributes);
		$field = Form::checkbox($name, $value, $checked, $attributes);
		return $this->build_wrapper($field, $name, $label);
	}

	/**
	 * Create a HTML file input element.
	 *
	 * @param  string  $name
	 * @param  array   $attributes
	 * @return string
	 */
	public function file($name, $label, $attributes = array())
	{
		$attributes = $this->set_attributes($name, $attributes);
		$field = Form::file($name, $attributes);
		return $this->build_wrapper($field, $name, $label);
	}

	private function build_wrapper($field, $name, $label = '')
	{
		$error = (isset(Session::get('errors')->messages[$name][0])) ? Session::get('errors')->messages[$name][0] : '';
		$class = 'control-group';
		if ( ! empty($this->control_group_error) && ! empty($error))
		{
		    $class .= ' ' . $this->control_group_error;
		}

		$out  = '<div class="'.$class.'">';
		$out .= $this->build_label($name, $label, false);
		$out .= '<div class="controls">'.PHP_EOL;
		$out .= $field;

		if ($this->display_inline_errors && ! empty($error))
		{
			$out .= '<span class="help-inline">'.$error.'</span>';
		}

		$out .= '</div>';
		$out .= '</div>'.PHP_EOL;
		return $out;
	}

	/**
	 * Builds the label html
	 *
	 * @param  string  $name The name of the html field
	 * @param  string  $label The label name
	 * @param  boolean $required
	 * @return string
	 */
	private function build_label($name, $label = '')
	{
		$out = '';
		if ( ! empty($label))
		{
			$class = 'control-label';
			if ( ! empty($this->required_label) && substr($label, -strlen($this->required_label)) == $this->required_label)
			{
				$label = $this->required_prefix . str_replace($this->required_label, '', $label) . $this->required_suffix;
				$class .= ' ' . $this->required_class;
			}
			$out .= Form::label($name, $label, array('class' => $class));
		}
		return $out;
	}

	/**
	 * Automatically populate the form field value
	 *
	 * @todo Note that there is s small error with checkboxes that are selected by default
	 * and then unselected by the user. If validation fails, then the checkbox will be
	 * selected again, because unselected checkboxes are not posted and there is no way
	 * to get this value after the redirect.
	 *
	 * @param  string $name Html form field to populate
	 * @param  string $value The default value for the field
	 * @return string
	 */
	private function calculate_value($name, $value = '')
	{
		$result = '';

		// First check if there is post data
		// This assumes that you are redirecting after failed post
		// and that you have flashed the data
		// @see http://laravel.com/docs/input#old-input
		if (Input::old($name) !== null)
		{
			$result = Input::old($name, $value);
		}

		// check if there is a default value set specifically for this field
		elseif ( ! empty($value))
		{
			$result = $value;
		}

		// lastly, check if any defaults have been set for the form as a whole
		elseif ( ! empty($this->defaults->$name))
		{
			$result = $this->defaults->$name;
		}
        return $result;
	}

	/**
	 * Create an id attribute for each field
	 * @param string $name The field name
	 * @param array  $attributes
	 */
	private function set_attributes($name, $attributes = array())
	{
		if ( ! $this->name_as_id or isset($attributes['id']))
		{
			return $attributes;
		}
		$attributes['id'] = $this->id_prefix . $name;
		return $attributes;
	}

	/**
	 * Create a group of form actions (buttons).
	 *
	 * @param  mixed  $buttons  String or array of HTML buttons.
	 * @return string
	 */
	public function actions($buttons)
	{
		$out  = '<div class="form-actions">';
		$out .= is_array($buttons) ? implode('', $buttons) : $buttons;
		$out .= '</div>';

		return $out;
	}

	/**
	 * Create a HTML submit input element.
	 *
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function submit($value, $attributes = array(), $btn_class = 'btn')
	{
		$attributes['type'] = 'submit';
		if ($btn_class != 'btn')
		{
			$btn_class = 'btn btn-' . $btn_class;
		}
		if ( ! isset($attributes['class']))
		{
			$attributes['class'] = $btn_class;
		}
		elseif (strpos($attributes['class'], $btn_class) === false)
		{
			$attributes['class'] .= ' ' . $btn_class;
		}

		return Form::button($value, $attributes);
	}

	/**
	 * Shortcut method for creating a default submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_default($value, $attributes = array())
	{
		return $this->submit($value, $attributes);
	}

	/**
	 * Shortcut method for creating a primary submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_primary($value, $attributes = array())
	{
		return $this->submit($value, $attributes, 'primary');
	}

	/**
	 * Shortcut method for creating an info submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_info($value, $attributes = array())
	{
		return $this->submit($value, $attributes, 'info');
	}

	/**
	 * Shortcut method for creating a success submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_success($value, $attributes = array())
	{
		return $this->submit($value, $attributes, 'success');
	}

	/**
	 * Shortcut method for creating a warning submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_warning($value, $attributes = array())
	{
		return $this->submit($value, $attributes, 'warning');
	}

	/**
	 * Shortcut method for creating a danger submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_danger($value, $attributes = array())
	{
		return $this->submit($value, $attributes, 'danger');
	}

	/**
	 * Shortcut method for creating an inverse submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return [type]
	 */
	public function submit_inverse($value, $attributes = array())
	{
		return $this->submit($value, $attributes, 'inverse');
	}

	/**
	 * Create a HTML reset input element.
	 *
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function reset($value, $attributes = array())
	{
		$attributes['type'] = 'reset';
		$attributes['class'] .= ' btn';
		return Form::button($value, $attributes);
	}

	/**
	 * Create a Form close element
	 */
	public function close()
	{
		return Form::close();
	}

}