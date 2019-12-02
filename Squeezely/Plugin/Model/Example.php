<?php
namespace Squeezely\Plugin\Model;

class Example {
    public function getMessage($thing='world', $should_lc=false) {
        $string = 'Hello ' . $thing . '!';
        if($should_lc) {
            $string = strToLower($string);
        }

        return $string;
    }
}
