<?php

/**
 *
 *
 * @package decorator
 * @subpackage html_decorator
 *
 * @author ebollens
 * @copyright Copyright (c) 2010-11 UC Regents
 * @license http://mwf.ucla.edu/license
 * @version 20110620
 *
 * @uses Decorator
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/decorator.class.php');

class Tag_HTML_Decorator extends Decorator {

    private $_tag_open;
    private $_tag_close;
    private $_inner = array();

    public function __construct($tag, $inner = '', $params = array()) {
        $this->_tag_open = HTML_Decorator::tag_open($tag, $params);
        $this->_tag_close = HTML_Decorator::tag_close($tag);
        $this->add_inner($inner);
    }

    public function &set_param($key, $val) {
        $this->_tag_open->set_param($key, $val);
        return $this;
    }

    public function &set_params($params) {
        $this->_tag_open->set_params($params);
        return $this;
    }

    public function &add_class($class) {
        $this->_tag_open->add_class($class);
        return $this;
    }

    public function &remove_class($class) {
        $this->_tag_open->remove_class($class);
        return $this;
    }

    public function &add_inner_tag($tag, $inner = '', $params = array()) {
        return $this->add_inner(new Tag_HTML_Decorator($tag, $inner, $params));
    }

    public function &add_inner_tag_front($tag, $inner = '', $params = array()) {
        return $this->add_inner_front(new Tag_HTML_Decorator($tag, $inner, $params));
    }

    /**
     * Checks if string might contain HTML tags or HTML entities.
     * 
     * @param mixed $content
     */
    private function _check_and_warn_about_raw_html($content) {
        if (is_string($content) && preg_match('/[<>&]/', $content) === 1) {
            $backtrace = '';
            $call_stack = debug_backtrace();
            for ($i = 1; $i < count($call_stack); $i++) {
                $call = $call_stack[$i];
                if (isset($call['function']) && isset($call['file']) && isset($call['line'])) {
                    $backtrace = $backtrace . "\n    #${i} ${call['function']}() called at [${call['file']}:${call['line']}]";
                }
            }
            trigger_error('Raw HTML or HTML entities are possibly being passed to a Decorator object. ' .
                    'In MWF 1.3, these strings will have special characters replaced with HTML entities. ' .
                    'You should generate HTML using Decorators rather than sending raw HTML. ' .
                    'The string in question is: "' . $content . '"' .
                    $backtrace, E_USER_WARNING);
        }
    }

    public function &add_inner($content) {
        $this->_check_and_warn_about_raw_html($content);

        if (is_array($content))
            foreach ($content as $c)
                $this->add_inner($c);
        else if ($content !== false)
            $this->_inner[] = $content;
        return $this;
    }

    public function &add_inner_front($content) {
        $this->_check_and_warn_about_raw_html($content);

        if (is_array($content))
            for ($i = count($content) - 1; $i >= 0; $i--)
                $this->add_inner_front($content[$i]);
        elseif ($content !== false)
            array_unshift($this->_inner, $content);
        return $this;
    }

    public function &flush_inner() {
        $this->_inner = array();
        return $this;
    }

    public function render() {
        $str = $this->_tag_open->render();

        if (count($this->_inner) === 0)
            return $str;

        foreach ($this->_inner as $inner)
            $str .= is_a($inner, 'Decorator') ? $inner->render() : $inner;

        $str .= $this->_tag_close->render();
        return $str;
    }

}
