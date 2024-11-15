<?php

namespace Veracrypt\CrashCollector;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    protected $logFile;
    protected $logLevel;
    /** @var Logger[] $_loggers  */
    protected static array $_loggers = [];

    // same weights as monolog
    protected $levelWeights = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /**
     * Loggers do not really need to be singletons, but doing things this way helps with keeping the configuration
     * for each logger tucked up neatly in a single place
     * @throws \DomainException
     */
    public static function getInstance(string $name): Logger
    {
        if (!array_key_exists($name, self::$_loggers)) {
            switch ($name) {
                case 'audit':
                    self::$_loggers[$name] = new self($_ENV['AUDIT_LOG_FILE'], $_ENV['AUDIT_LOG_LEVEL']);
                    break;
                default:
                    throw new \DomainException("Logger '$name' in not configured");
            }
        }

        return self::$_loggers[$name];
    }

    public function __construct(string|\Stringable $logFile, $logLevel)
    {
        if (!str_contains('/', $logFile)) {
            $logDir = $_ENV['LOG_DIR'];
            if ($logDir != '') {
                $logDir = rtrim($logDir, '/') . '/';
            }
            $logFile = $logDir . $logFile;
        }
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        /// @todo throw a \DomainException if $level is unsupported

        if ($this->levelWeights[$this->logLevel] > $this->levelWeights[$level]) {
            return;
        }

        file_put_contents($this->logFile,
            date('c') . ' ' . strtoupper($level) . ' ' . $message . ' ' . str_replace("\n", ' ', json_encode($context)) . "\n",
            FILE_APPEND
        );
    }
}
