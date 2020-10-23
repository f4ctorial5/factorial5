<?php

class PhpHBObfuscator {

    public $comment_chance = 10;
    public $comment_file = "hb.txt";
    public $min_spaces = 8;
    public $max_spaces = 16;
    private $_comments = [];
    private $_first_letter = "h";
    private $_last_letter = "b";
    private $_chars = ["d", "n", "o", "a", "j"];
    private $_code = "";
    private $_functions = [];
    private $_defines = [];
    private $_variables = [];
    private $_used_var_names = [];

    public function __construct($first_char = "h", $last_char = "b", $chars = ["d", "n", "o", "a", "j"]) {
        $this->_first_letter = $first_char;
        $this->_last_letter = $last_char;
        $this->_chars = $chars;
    }

    private function _find_entities() {
        if (preg_match_all("#\\$([A-Za-z_][a-zA-Z0-9_]*)[\-=\s;\(\)\[\]]#i", $this->_code, $vars)) {
            foreach ($vars[1] as $var_name) {
                if (!in_array($var_name, $this->_variables))
                    if (in_array($var_name, array('_GET',
                                '_POST',
                                '_COOKIE',
                                'GLOBALS',
                                '_REQUEST',
                                '_SERVER',
                                    )
                            ))
                        continue;
                $this->_variables[] = $var_name;
            }
        }

        if (preg_match_all("#function\s+([A-Za-z_][a-zA-Z0-9_]*)\s*\(#i", $this->_code, $vars)) {
            foreach ($vars[1] as $func_name) {
                if (!in_array($func_name, $this->_functions))
                    $this->_functions[] = $func_name;
            }
        }

        if (preg_match_all("#define\s*\(\s*['\"]([a-zA-Z_][a-zA-Z0-9_]*?)['\"]#i", $this->_code, $vars)) {
            foreach ($vars[1] as $define) {
                if (!in_array($define, $this->_defines))
                    $this->_defines[] = $define;
            }
        }
    }

    private function _name_generator() {
        shuffle($this->_chars);
        $name = $this->_first_letter . implode("", $this->_chars) . $this->_last_letter;
        if (in_array($name, $this->_used_var_names))
            return $this->_name_generator();
        $this->_used_var_names[] = $name;
        return $name;
    }

    private function _read_comments() {
        $f = fopen($this->comment_file, "r");

        while (!feof($f)) {
            $line = trim(fgets($f));
            if ($line)
                $this->_comments[] = $line;
        }
    }

    public function do_hb($code) {
        $this->_code = $code;
        $this->_find_entities();
        if ($this->comment_chance > 0) {
            $this->_read_comments();
        }

        $max = 1;
        $len = sizeof($this->_chars);
        for ($i = 1; $i <= sizeof($this->_chars); $i++) {
            $max *= $i;
        }

        $total = sizeof($this->_variables) + sizeof($this->_functions) + sizeof($this->_defines);
        if ($total > $max) {
            echo "Max $max names for $len chars.";
            return false;
        }

        $replacement = [];
        foreach ($this->_variables as $var) {
            $replacement['$' . $var] = '$' . $this->_name_generator();
        }

        foreach ($this->_functions as $func) {
            $replacement[$func] = $this->_name_generator();
        }

        foreach ($this->_defines as $def) {
            $replacement[$def] = $this->_name_generator();
        }

        $r = strtr($code, $replacement);
        $lines = explode("\n", $r);
        $hblines = [];

        for ($i = 0; $i < sizeof($lines); $i++) {
            $line = trim($lines[$i]);
            $hblines[] = str_repeat(" ", mt_rand($this->min_spaces, $this->max_spaces)) . $line;
            $chance = mt_rand(0, 100);
            if (($chance < $this->comment_chance)) {
                $hblines[] = "//" . $this->_comments[mt_rand(0, sizeof($this->_comments))];
            }
        }

        return implode("\r\n", $hblines);
    }

}
