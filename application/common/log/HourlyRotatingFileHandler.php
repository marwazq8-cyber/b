<?php

namespace app\common\log;


use Monolog\Handler\RotatingFileHandler;

class HourlyRotatingFileHandler extends RotatingFileHandler
{
    /**
     * Overriding this to append 'H' to the end of the filename (before the extension).
     */
    protected function getTimedFilename(): string
    {
        $fileInfo = pathinfo($this->filename);
        $timedFilename = str_replace(
            $fileInfo['extension'],
            date('Y-m-d-H') . '.' . $fileInfo['extension'],
            $this->filename
        );

        return $timedFilename;
    }

    /**
     * Overridden to include check for 'H' in addition to 'Ymd'
     */
    protected function timedFilenameFormat($filename)
    {
        return preg_replace('{^log\.}i', sprintf('log-%s.', date('Y-m-d-H')), $filename);
    }
}