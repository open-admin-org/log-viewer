<?php

namespace OpenAdmin\Admin\LogViewer;

use OpenAdmin\Admin\Extension;

/**
 * Class LogViewer.
 */
class LogViewer extends Extension
{
    use BootExtension;

    /**
     * The log file name.
     *
     * @var string
     */
    public $file;

    /**
     * Enable bypass of protected files in request.
     *
     * @var bool
     */
    public $bypass_protected_urls = false;

    /**
     * Array with strings to find in filenames.
     *
     * @var array
     */
    public $bypass_protected_urls_find = [];

    /**
     * Array with strings to replace in filenames.
     *
     * @var array
     */
    public $bypass_protected_urls_replace = [];

    /**
     * The path of log file.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Start and end offset in current page.
     *
     * @var array
     */
    protected $pageOffset = [];

    /**
     * @var array
     */
    public static $levelColors = [
        'EMERGENCY' => 'danger',
        'ALERT'     => 'primary',
        'CRITICAL'  => 'danger',
        'ERROR'     => 'warning',
        'WARNING'   => 'warning',
        'NOTICE'    => 'info',
        'INFO'      => 'info',
        'DEBUG'     => 'success',
    ];

    /**
     * LogViewer constructor.
     *
     * @param null $file
     */
    public function __construct($file = null)
    {
        $this->bypass_protected_urls = static::config('bypass_protected_urls', false);
        if ($this->bypass_protected_urls) {
            $this->bypass_protected_urls_find    = static::config('bypass_protected_urls_find', ['.']);
            $this->bypass_protected_urls_replace = static::config('bypass_protected_urls_replace', ['[dot]']);
        }

        if (is_null($file)) {
            $file = $this->getLastModifiedLog();
        }

        if ($this->bypass_protected_urls) {
            $file = $this->revert_bypass($file);
        }

        $this->file = $file;

        $this->getFilePath();
    }

    public function set_bypass($file)
    {
        return str_replace($this->bypass_protected_urls_find, $this->bypass_protected_urls_replace, $file);
    }

    public function revert_bypass($file)
    {
        return str_replace($this->bypass_protected_urls_replace, $this->bypass_protected_urls_find, $file);
    }

    /**
     * Get file path by giving log file name.
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getFilePath()
    {
        if (!$this->filePath) {
            $path = sprintf(storage_path('logs/%s'), $this->file);

            if (!file_exists($path)) {
                throw new \Exception('log not exists!');
            }

            $this->filePath = $path;
        }

        return $this->filePath;
    }

    /**
     * Get size of log file.
     *
     * @return int
     */
    public function getFilesize()
    {
        return filesize($this->filePath);
    }

    /**
     * Get log file list in storage.
     *
     * @param int $count
     *
     * @return array
     */
    public function getLogFiles($count = 20, $only_files = false)
    {
        $files = glob(storage_path('logs/*'));
        if ($only_files) {
            $files = array_filter($files, 'is_file');
        }
        $files = array_combine($files, array_map('filemtime', $files));
        arsort($files);

        $files = array_map('basename', array_keys($files));

        return array_slice($files, 0, $count);
    }

    /**
     * Get the last modified log file.
     *
     * @return string
     */
    public function getLastModifiedLog()
    {
        $logs = $this->getLogFiles(20, true);

        return current($logs);
    }

    /**
     * Get previous page url.
     *
     * @return bool|string
     */
    public function getPrevPageUrl()
    {
        if ($this->pageOffset['end'] >= $this->getFilesize() - 1) {
            return false;
        }

        return route('log-viewer-file', [
            'file' => $this->set_bypass($this->file), 'offset' => $this->pageOffset['end'],
        ]);
    }

    /**
     * Get Next page url.
     *
     * @return bool|string
     */
    public function getNextPageUrl()
    {
        if ($this->pageOffset['start'] == 0) {
            return false;
        }

        return route('log-viewer-file', [
            'file' => $this->set_bypass($this->file), 'offset' => -$this->pageOffset['start'],
        ]);
    }

    /**
     * Fetch logs by giving offset.
     *
     * @param int $seek
     * @param int $lines
     * @param int $buffer
     *
     * @return array
     *
     * @see http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     */
    public function fetch($seek = 0, $lines = 20, $buffer = 4096)
    {
        if (!file_exists($this->filePath) || is_dir($this->filePath)) {
            $this->pageOffset = ['start' => 0, 'end' => 0];

            return $this->parseLog('');
        }
        $f = fopen($this->filePath, 'rb');

        if ($seek) {
            fseek($f, abs($seek));
        } else {
            fseek($f, 0, SEEK_END);
        }

        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }
        fseek($f, -1, SEEK_CUR);

        // Start reading
        if ($seek > 0) {
            $output = '';

            $this->pageOffset['start'] = ftell($f);

            while (!feof($f) && $lines >= 0) {
                $output = $output.($chunk = fread($f, $buffer));
                $lines -= substr_count($chunk, "\n[20");
            }

            $this->pageOffset['end'] = ftell($f);

            while ($lines++ < 0) {
                $strpos = strrpos($output, "\n[20") + 1;
                $_      = mb_strlen($output, '8bit') - $strpos;
                $output = substr($output, 0, $strpos);
                $this->pageOffset['end'] -= $_;
            }
        } else {
            $output = '';

            $this->pageOffset['end'] = ftell($f);

            while (ftell($f) > 0 && $lines >= 0) {
                $offset = min(ftell($f), $buffer);
                fseek($f, -$offset, SEEK_CUR);
                $output = ($chunk = fread($f, $offset)).$output;
                fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                $lines -= substr_count($chunk, "\n[20");
            }

            $this->pageOffset['start'] = ftell($f);

            while ($lines++ < 0) {
                $strpos = strpos($output, "\n[20") + 1;
                $output = substr($output, $strpos);
                $this->pageOffset['start'] += $strpos;
            }
        }

        fclose($f);

        return $this->parseLog($output);
    }

    /**
     * Get tail logs in log file.
     *
     * @param int $seek
     *
     * @return array
     */
    public function tail($seek)
    {
        // Open the file
        $f = fopen($this->filePath, 'rb');

        if (!$seek) {
            // Jump to last character
            fseek($f, -1, SEEK_END);
        } else {
            fseek($f, abs($seek));
        }

        $output = '';

        while (!feof($f)) {
            $output .= fread($f, 4096);
        }

        $pos = ftell($f);

        fclose($f);

        $logs = [];

        foreach ($this->parseLog(trim($output)) as $log) {
            $logs[] = $this->renderTableRow($log);
        }

        return [$pos, $logs];
    }

    /**
     * Render table row.
     *
     * @param $log
     *
     * @return string
     */
    protected function renderTableRow($log)
    {
        $color = self::$levelColors[$log['level']] ?? 'black';

        $index = uniqid();

        $button = '';

        if (!empty($log['trace'])) {
            $button = "<a class=\"btn btn-primary btn-xs\" data-toggle=\"collapse\" data-target=\".trace-{$index}\"><i class=\"fa fa-info\"></i>&nbsp;&nbsp;Exception</a>";
        }

        $trace = '';

        if (!empty($log['trace'])) {
            $trace = "<tr class=\"collapse trace-{$index}\">
    <td colspan=\"5\"><div style=\"white-space: pre-wrap;background: #333;color: #fff; padding: 10px;\">{$log['trace']}</div></td>
</tr>";
        }

        return <<<TPL
<tr style="background-color: rgb(255, 255, 213);">
    <td><span class="label bg-{$color}">{$log['level']}</span></td>
    <td><strong>{$log['env']}</strong></td>
    <td  style="width:150px;">{$log['time']}</td>
    <td><code>{$log['info']}</code></td>
    <td>$button</td>
</tr>
$trace
TPL;
    }

    /**
     * Parse raw log text to array.
     *
     * @param $raw
     *
     * @return array
     */
    protected function parseLog($raw)
    {
        $logs = preg_split('/\[(\d{4}(?:-\d{2}){2} \d{2}(?::\d{2}){2})\] (\w+)\.(\w+):((?:(?!{"exception").)*)?/', trim($raw), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($logs as $index => $log) {
            if (preg_match('/^\d{4}/', $log)) {
                break;
            } else {
                unset($logs[$index]);
            }
        }

        if (empty($logs)) {
            return [];
        }

        $parsed = [];

        foreach (array_chunk($logs, 5) as $log) {
            $parsed[] = [
                'time'  => $log[0] ?? '',
                'env'   => $log[1] ?? '',
                'level' => $log[2] ?? '',
                'info'  => $log[3] ?? '',
                'trace' => trim($log[4] ?? ''),
            ];
        }

        unset($logs);

        rsort($parsed);

        return $parsed;
    }
}
