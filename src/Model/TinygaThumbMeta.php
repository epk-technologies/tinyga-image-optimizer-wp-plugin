<?php

namespace Tinyga\Model;

class TinygaThumbMeta extends BaseModel
{
    const OPTION_NAME = '_tinyga_thumbs_meta';

    const THUMB = 'thumb';
    const FILE = 'file';
    const ORIGINAL_SIZE = 'original_size';
    const OPTIMIZED_SIZE = 'optimized_size';
    const OPTIMIZATION_QUALITY = 'optimization_quality';

    /**
     * @var string
     */
    protected $thumb;

    /**
     * @var string
     */
    protected $file;

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
    protected $optimization_quality;

    /**
     * @return string|null
     */
    public function getThumb()
    {
        return $this->thumb;
    }

    /**
     * @param string|null $thumb
     */
    public function setThumb($thumb)
    {
        $this->thumb = $thumb;
    }

    /**
     * @return string|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string|null $file
     */
    public function setFile($file)
    {
        $this->file = $file;
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
}
