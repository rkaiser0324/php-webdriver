<?php
class WebDriverHelper {
    // Screenshots come back as a base64-encoded PNG...But we
    // almost always want the PNG, so decode.
    public static function screenshot($session) {
        $screenshot = $session->screenshot();
        return base64_decode($screenshot);
    }

    // It's a pain to build an array of values, so do it here
    public static function value($element,$value_string) {
        $value_array = array("value" => preg_split('//u', $value_string, -1, PREG_SPLIT_NO_EMPTY));
        return $element->value($value_array);
    }

    // A little syntactic sugar for selecting a frame
    public static function frame($session,$id) {
        $id_array = array("id" => $id);
        return $session->frame($id_array);
    }
}
