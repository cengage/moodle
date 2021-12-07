<?php
class filter_lti extends moodle_text_filter {

    public function filter($text, array $options = array()) {
    
        if (!is_string($text) or empty($text) or stripos($text, 'href="/lib/editor/atto/plugins/lti/launch.php') === false ) {
            return $text;
        }

        $matches = preg_split('/(<a.*?<\/a>)/i', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (!$matches) {
            return $text;
        }
        $newtext = '';

        foreach ($matches as $idx => $val) {
            if (stripos($val, "<a ") === 0 && preg_match('/href=\"(\/lib\/editor\/atto\/plugins\/lti\/launch\.php.*?)\"/', $val, $hrefmatches) !== false) {
                // iframe if only embed, window.open otherwise to keep the relationship with opener
                $newtext.="<iframe src=\"$hrefmatches[1]\"></iframe>";
            } else {
                $newtext.=$val;
            }
        }
        return $newtext;
    }

}