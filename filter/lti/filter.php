<?php
class filter_lti extends moodle_text_filter {

    public function filter($text, array $options = array()) {
   
        $coursecontext = $this->context->get_course_context(false);
        // LTI launches for now only execute in course contexts.
        if ($coursecontext && !is_string($text) or empty($text) or stripos($text, 'data-lti') === false ) {
            return $text;
        }

        $matches = preg_split('/(<a.*?<\/a>)/i', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (!$matches) {
            return $text;
        }
        $newtext = '';

        foreach ($matches as $idx => $val) {
            if (stripos($val, "<a ") === 0 
                && preg_match('/data-lti=\"([^\"]*)/', $val, $ltidatamatches) === 1  
                && preg_match('/href=\"([^\"]*)/', $val, $hrefmatches) === 1) {
                $href = $hrefmatches[1];
                $sep = str_contains($href, '?')?'&':'?';
                $href = $href.$sep."courseid=".$coursecontext->instanceid;
                if (strpos($ltidatamatches[1], 'embed') === 0) {
                    $width = '90%';
                    $height = '400px';
                    if (preg_match('/width:([^;]*)/', $ltidatamatches[1], $widthmatches) === 1) {
                        $width = $widthmatches[1];
                    }
                    if (preg_match('/height:([^;1]*)/', $ltidatamatches[1], $heightmatches) === 1) {
                        $height = $heightmatches[1];
                    }
                    $newtext.="<iframe class=\"ltiembed\" src=\"$href\" style=\"width:$width;height:$height\"></iframe>";
                } else {
                    $newtext.=str_replace($hrefmatches[1], $href, $val);
                }
            } else {
                $newtext.=$val;
            }
        }
        return $newtext;
    }

}