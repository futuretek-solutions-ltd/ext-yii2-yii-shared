<?php

namespace futuretek\yii\shared\log;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\log\LogRuntimeException;
use yii\log\Target;

/**
 * FileDateTarget records log messages in a file.
 *
 * The log file is specified via [[logFile]]. Log file rotation is performed every midnight,
 * which renames the current log file by suffixing the base file name part with date in Y-m-d format.
 * The property [[maxLogFiles]] specifies how many history files to keep.
 */
class FileDateTarget extends Target
{
    /**
     * @var string|null log file path or [path alias](guide:concept-aliases). If not set, it will use the "@runtime/logs/app.log" file.
     * The directory containing the log files will be automatically created if not existing.
     */
    public $logFile;

    /**
     * @var bool whether log files should be rotated. Rotation happens every day at midnight.
     * Log filename from previous day is appended with date.
     * Log rotation is enabled by default. This property allows you to disable it, when you have configured
     * an external tools for log rotation on your server.
     */
    public $enableRotation = true;

    /**
     * @var int number of log files used for rotation. If set to 0, no old file retention is active. Defaults to 0.
     */
    public $maxLogFiles = 0;

    /**
     * @var int|null the permission to be set for newly created log files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;

    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
        if ($this->logFile === null) {
            $this->logFile = Yii::$app->getRuntimePath() . '/logs/app.log';
        } else {
            $this->logFile = Yii::getAlias($this->logFile);
        }
        if ($this->maxLogFiles < 0) {
            $this->maxLogFiles = 0;
        }
    }

    /**
     * Writes log messages to a file.
     * @throws InvalidConfigException if unable to open the log file for writing
     * @throws LogRuntimeException if unable to write complete log to file
     */
    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";

        if (trim($text) === '') {
            return; // No messages to export, so we exit the function early
        }

        if (strpos($this->logFile, '://') === false || strncmp($this->logFile, 'file://', 7) === 0) {
            $logPath = dirname($this->logFile);
            FileHelper::createDirectory($logPath, $this->dirMode, true);
        }

        if ($this->enableRotation && is_file($this->logFile)) {
            $midnight = strtotime('midnight');
            $filectime = filectime($this->logFile);
            if ($filectime < $midnight) {
                $yesterday = strtotime('-1 day', $midnight);
                $this->rotateFile(min($filectime, $yesterday));
                if ($this->maxLogFiles > 0) {
                    $this->cleanOldLogFiles();
                }
            }
        }

        if (($fp = @fopen($this->logFile, 'ab')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        $writeResult = @fwrite($fp, $text);
        if ($writeResult === false) {
            $message = "Unable to export log through file ($this->logFile)!";
            if ($error = error_get_last()) {
                $message .= ": {$error['message']}";
            }
            throw new LogRuntimeException($message);
        }
        $textSize = strlen($text);
        if ($writeResult < $textSize) {
            throw new LogRuntimeException("Unable to export whole log through file ({$this->logFile})! Wrote $writeResult out of $textSize bytes.");
        }
        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }

    protected function cleanOldLogFiles(): void
    {
        $fileInfo = pathinfo(Yii::getAlias($this->logFile));
        $extension = isset($fileInfo['extension']) ? ".{$fileInfo['extension']}" : '';
        $searchPattern = "{$fileInfo['filename']}-*$extension";
        $regexPattern = "/^{$fileInfo['filename']}-(\d{4}-\d{2}-\d{2})" . ($extension ? "\\$extension" : '') . '$/';
        $logFiles = FileHelper::findFiles($fileInfo['dirname'], [
            'only' => [$searchPattern],
            'recursive' => false,
        ]);

        $datedLogs = [];
        foreach ($logFiles as $filePath) {
            $filename = basename($filePath);
            if (preg_match($regexPattern, $filename, $matches) && isset($matches[1])) {
                $datedLogs[] = [
                    'file' => $filePath,
                    'date' => $matches[1],
                ];
            }
        }

        usort($datedLogs, static fn($a, $b) => $b['date'] <=> $a['date']);
        $logsToRemove = array_slice($datedLogs, $this->maxLogFiles);

        foreach ($logsToRemove as $logEntry) {
            FileHelper::unlink($logEntry['file']);
        }
    }

    /**
     * Rotates log file.
     */
    protected function rotateFile(int $date): void
    {
        $oldFileName = Yii::getAlias($this->logFile);
        $fileInfo = pathinfo($oldFileName);
        $extension = isset($fileInfo['extension']) ? ".{$fileInfo['extension']}" : '';
        $newFileName = "{$fileInfo['dirname']}/{$fileInfo['filename']}-" . date('Y-m-d', $date) . $extension;

        if (is_file($oldFileName)) {
            $result = rename($oldFileName, $newFileName);
            if (!$result) {
                // The problem with Windows systems where the rename() function does not work with files that are opened by some
                // process is described in a comment by Martin Pelletier https://www.php.net/manual/en/function.rename.php#102274
                // in the PHP documentation. The way around this is to copy and clear the original file.
                $this->moveByCopy($oldFileName, $newFileName);
                $this->clearLogFile($oldFileName);
            }
        }
    }

    /***
     * Clear log file without closing any other process open handles
     * @param string $file
     */
    private function clearLogFile(string $file): void
    {
        if ($filePointer = @fopen($file, 'ab')) {
            @ftruncate($filePointer, 0);
            @fclose($filePointer);
        }
    }

    /***
     * Copy file into new file
     * @param string $oldFile
     * @param string $newFile
     */
    private function moveByCopy(string $oldFile, string $newFile): void
    {
        @copy($oldFile, $newFile);
        if ($this->fileMode !== null) {
            @chmod($newFile, $this->fileMode);
        }
    }
}
