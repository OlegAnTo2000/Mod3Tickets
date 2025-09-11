<?php

/**
 * @param $filename
 *
 * @return string
 */
if (!function_exists('getSnippetContent')) {
    function getSnippetContent($filename)
    {
        $file = trim(file_get_contents($filename));
        preg_match('#\<\?php(.*)#is', $file, $data);

        return rtrim(rtrim(trim($data[1]), '?>'));
    }
}


/**
 * Recursive directory remove
 *
 * @param $dir
 */
if (!function_exists('rrmdir')) {
    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}