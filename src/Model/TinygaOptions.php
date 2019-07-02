<?php

namespace Tinyga\Model;

use Tinyga\ImageOptimizer\OptimizationRequest;

class TinygaOptions extends BaseModel
{
    const OPTION_NAME = '_tinyga_options';

    /**
     * @var string
     */
    protected $api_key = '';

    /**
     * @var bool
     */
    protected $auto_optimize = 1;

    /**
     * @var bool
     */
    protected $optimize_main_image = 1;

    /**
     * @var int
     */
    protected $quality = OptimizationRequest::DEFAULT_LOSSY_QUALITY;

    /**
     * @var array
     */
    protected $sizes;

    /**
     * @var bool
     */
    protected $show_reset = 0;

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @param string $api_key
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * @return bool
     */
    public function isAutoOptimize()
    {
        return $this->auto_optimize;
    }

    /**
     * @param bool $auto_optimize
     */
    public function setAutoOptimize($auto_optimize)
    {
        $this->auto_optimize = $auto_optimize;
    }

    /**
     * @return bool
     */
    public function isOptimizeMainImage()
    {
        return $this->optimize_main_image;
    }

    /**
     * @param bool $optimize_main_image
     */
    public function setOptimizeMainImage($optimize_main_image)
    {
        $this->optimize_main_image = $optimize_main_image;
    }

    /**
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param int $quality
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }

    /**
     * @return array
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * @param array $sizes
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;
    }

    /**
     * @param string $size
     *
     * @return bool
     */
    public function isValidSize($size)
    {
        return $this->sizes[$size];
    }

    /**
     * @return bool
     */
    public function isShowReset()
    {
        return $this->show_reset;
    }

    /**
     * @param bool $show_reset
     */
    public function setShowReset($show_reset)
    {
        $this->show_reset = $show_reset;
    }
}
