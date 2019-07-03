<?php

namespace Tinyga\Model;

class TinygaImageMeta extends BaseModel
{
    const OPTION_NAME = '_tinyga_image_meta';

    const TASK_ID = 'task_id';
    const ORIGINAL_SIZE = 'original_size';
    const OPTIMIZED_SIZE = 'optimized_size';
    const SAVED_BYTES = 'saved_bytes';
    const SAVING_PERCENT = 'savings_percent';
    const OPTIMIZATION_QUALITY = 'optimization_quality';
    const WIDTH = 'width';
    const HEIGHT = 'height';
    const META = 'meta';
    const OPTIMIZED_BACKUP_FILE = 'optimized_backup_file';
    const ERROR_CODE = 'error_code';
    const CODE = 'code';
    const MESSAGE = 'message';

    /**
     * @var int
     */
    protected $task_id;

    /**
     * @var int
     */
    protected $original_size;

    /**
     * @var int
     */
    protected $optimized_size;

    /**
     * @var int
     */
    protected $saved_bytes;

    /**
     * @var string
     */
    protected $savings_percent;

    /**
     * @var int
     */
    protected $optimization_quality;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var string
     */
    protected $meta;

    /**
     * @var string
     */
    protected $optimized_backup_file;

    /**
     * @var string
     */
    protected $error_code;

    /**
     * @var int|mixed
     */
    protected $code;

    /**
     * @var string
     */
    protected $message;

    /**
     * @return int|null
     */
    public function getTaskId()
    {
        return $this->task_id;
    }

    /**
     * @param int|null $task_id
     */
    public function setTaskId($task_id)
    {
        $this->task_id = $task_id;
    }

    /**
     * @return int|null
     */
    public function getOriginalSize()
    {
        return $this->original_size;
    }

    /**
     * @param int|null $original_size
     */
    public function setOriginalSize($original_size)
    {
        $this->original_size = $original_size;
    }

    /**
     * @return int|null
     */
    public function getOptimizedSize()
    {
        return $this->optimized_size;
    }

    /**
     * @param int|null $optimized_size
     */
    public function setOptimizedSize($optimized_size)
    {
        $this->optimized_size = $optimized_size;
    }

    /**
     * @return int|null
     */
    public function getSavedBytes()
    {
        return $this->saved_bytes;
    }

    /**
     * @param int|null $saved_bytes
     */
    public function setSavedBytes($saved_bytes)
    {
        $this->saved_bytes = $saved_bytes;
    }

    /**
     * @return string|null
     */
    public function getSavingsPercent()
    {
        return $this->savings_percent;
    }

    /**
     * @param string|null $savings_percent
     */
    public function setSavingsPercent($savings_percent)
    {
        $this->savings_percent = $savings_percent;
    }

    /**
     * @return int|null
     */
    public function getOptimizationQuality()
    {
        return $this->optimization_quality;
    }

    /**
     * @param int|null $optimization_quality
     */
    public function setOptimizationQuality($optimization_quality)
    {
        $this->optimization_quality = $optimization_quality;
    }

    /**
     * @return int|null
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int|null
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int|null $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string|null
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param string|null $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * @return string|null
     */
    public function getOptimizedBackupFile()
    {
        return $this->optimized_backup_file;
    }

    /**
     * @param string|null $optimized_backup_file
     */
    public function setOptimizedBackupFile($optimized_backup_file)
    {
        $this->optimized_backup_file = $optimized_backup_file;
    }

    /**
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @param string|null $error_code
     */
    public function setErrorCode($error_code)
    {
        $this->error_code = $error_code;
    }

    /**
     * @return int|mixed|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int|mixed|null $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
