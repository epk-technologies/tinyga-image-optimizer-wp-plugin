<?php

namespace Tinyga\Model;

class WPAttachmentMeta extends BaseModel
{
    const OPTION_NAME = '_wp_attachment_metadata';

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
    protected $file;

    /**
     * @var array
     */
    protected $sizes = [];

    /**
     * @var array
     */
    protected $image_meta = [];

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
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
     * @return array
     */
    public function getImageMeta()
    {
        return $this->image_meta;
    }

    /**
     * @param array $image_meta
     */
    public function setImageMeta($image_meta)
    {
        $this->image_meta = $image_meta;
    }
}
