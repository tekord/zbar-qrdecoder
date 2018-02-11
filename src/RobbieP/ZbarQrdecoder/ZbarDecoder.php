<?php

namespace RobbieP\ZbarQrdecoder;

use RobbieP\ZbarQrdecoder\Result\ErrorResult;
use RobbieP\ZbarQrdecoder\Result\Result;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ZbarDecoder {

    const EXECUTABLE = 'zbarimg';

    private $path;
    private $file_path;
    private $result;

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     * @param Process $processBuilder
     */
    function __construct($config = [], $processBuilder = null)
    {
        $this->config = $config;
        if(isset($this->config['path'])) {
            $this->setPath($this->config['path']);
        }
    }

    /**
     * Main constructor - builds the process, runs it then returns the Result object
     * @param $filename
     * @return mixed
     * @throws \Exception
     */
    public function make($filename)
    {
        $this->setFilepath($filename);

		$process = new Process([
			$this->getPath() . DIRECTORY_SEPARATOR . static::EXECUTABLE,
			'-D', '--xml', '-q', $this->getFilepath()
		]);

		$process->enableOutput();
		$this->runProcess($process);

        return $this->output();
    }

    /**
     * Returns the path to the executable zbarimg
     * Defaults to /usr/bin
     * @throws \Exception
     * @return mixed
     */
    public function getPath()
    {
        if(! $this->path ) {
            $this->setPath('/usr/bin');
        }
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @return mixed
     */
    public function getFilepath()
    {
        return $this->file_path;
    }

    /**
     * @param mixed $filepath
     * @throws \Exception
     */
    public function setFilepath($filepath)
    {
        if(! is_file($filepath) ) {
            throw new \Exception('Invalid filepath given');
        }
        $this->file_path = $filepath;
    }

    /**
     * Runs the process
	 *
	 * @param Process $process
	 *
     * @throws \Exception
     */
    private function runProcess(Process $process)
    {
        try {
            $process->mustRun();
            $this->result = new Result($process->getOutput());
        } catch (ProcessFailedException $e) {
            switch($e->getProcess()->getExitCode()) {
                case 1:
                    throw new \Exception('An error occurred while processing the image. It could be bad arguments, I/O errors and image handling errors from ImageMagick');
                case 2:
                    throw new \Exception('ImageMagick fatal error');
                case 4:
                    $this->result = new ErrorResult('No barcode detected');
                    break;
                default:
                    throw new \Exception('Problem with decode - check you have zbar-tools installed');
            }
        }

    }

    /**
     * Only return the output class to the end user
     * @return mixed
     */
    private function output()
    {
        return $this->result;
    }

} 