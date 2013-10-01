<?php

/**
 * Abstract element processor
 *
 * @author Fabrizio Branca
 * @since 2013-08-30
 */
abstract class Aoe_Import_Model_Processor_Abstract implements Aoe_Import_Model_Processor_Interface
{

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var array messages
     */
    protected $messages = array();

    /**
     * @var array options
     */
    protected $options = array();

    /**
     * @var bool
     */
    protected $enableLogging = false;

    /**
     * @var string
     */
    protected $logFilePath;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var string
     */
    protected $path;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pid = getmypid();

        $this->logFilePath = str_replace(array(
            '###LOG_DIR###',
            '###PID###',
            '###NAME###'
        ), array(
            Mage::getBaseDir('log'),
            $this->pid,
            $this->getName()
        ), $this->logFilePath);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array $options
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setLogFilePath($logFilePath)
    {
        $this->logFilePath = $logFilePath;
    }

    /**
     * Set data
     *
     * @see setData()
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Reset processor object to be ready for reuse
     *
     * @return void
     */
    public function reset()
    {
        $this->data = NULL;
        $this->messages = array();
    }

    /**
     * Get processor name
     * Overwrite this if you want something else than the classname
     *
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * Run is a wrapper for the ->process() function. This one will be called from outside
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->process();
        } catch (Exception $e) {
            $this->addError($e->getMessage());
        }

        if ($this->logFilePath) {
            $res = file_put_contents($this->logFilePath, $this->getSummary(), FILE_APPEND);
            if ($res === false) {
                $this->addWarning('Error while writing log to ' . $this->logFilePath); // for direct output
            }
        }
    }

    /**
     * Execute something before looping
     * (Currently only used in csv processors)
     *
     * @return void
     */
    public function before()
    {

    }

    /**
     * Execute something after looping
     * (Currently only used in csv processors)
     *
     * @return void
     */
    public function after()
    {

    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Add info
     *
     * @param string $message
     */
    protected function addInfo($message) {
        $this->addMessage($message, Zend_Log::INFO);
    }

    /**
     * Add warning (import continues)
     *
     * @param $message
     */
    protected function addWarning($message) {
        $this->addMessage($message, Zend_Log::WARN);
    }

    /**
     * Add error (import stops)
     *
     * @param $message
     */
    protected function addError($message) {
        $this->addMessage($message, Zend_Log::ERR);
    }

    /**
     * Generic add message
     *
     * @param $message
     * @param $level
     */
    protected function addMessage($message, $level) {
        $this->messages[] = array(
            'level' => $level,
            'message' => $message
        );
    }

    /**
     * Get messages as string
     *
     * @return string
     */
    public function getSummary() {
        $result = sprintf("[--- Processed %s using processor '%s' ---]\n",
            $this->getPath(),
            $this->getName()
        );
        foreach ($this->messages as $message) {
            // for messages in the old format
            if (!is_array($message)) {
                $message = array(
                    'level' => Zend_Log::INFO,
                    'message' => $message
                );
            }
            switch ($message['level']) {
                case Zend_Log::INFO: $result .= '[INFO]'; break;
                case Zend_Log::WARN: $result .= '[WARNING]'; break;
                case Zend_Log::ERR: $result .= '[ERROR]'; break;
            }
            $result .= ' ' . $message['message'] . "\n";
        }
        $result .= "\n";
        return $result;
    }

}
