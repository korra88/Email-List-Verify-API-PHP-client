<?php

namespace EmailListVerify;

class FileInfo {

    public $file_id, $filename, $unique, $lines, $lines_processed, $status,
            $timestamp, $link, $link2;

    /**
     *
     * @param string $string
     */
    public function __construct($string) {
        list($this->file_id, $this->filename, $this->unique, $this->lines,
                $this->lines_processed, $this->status, $this->timestamp,
                $this->link1, $this->link2) = explode('|', $string);
    }

}
