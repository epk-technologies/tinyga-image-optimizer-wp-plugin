<?php

namespace Tinyga\Manager;

use Tinyga\ImageOptimizer\Image\ImageFile;
use Tinyga\ImageOptimizer\ImageOptimizerClient;
use Tinyga\ImageOptimizer\OptimizationException;
use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\ImageOptimizer\OptimizationResult;
use Tinyga\Model\TinygaOptions;

final class ImageOptimizationManager extends SettingsManager
{
    const MENU_SLUG = 'wp-tinyga';

    /**
     * @var ImageOptimizerClient
     */
    private $client;

    /**
     * @var TinygaOptions
     */
    private $tinyga_options;

    /**
     * @var OptimizationRequest
     */
    private $last_request;

    /**
     * Register action to event.
     */
    public function __construct()
    {
        $this->tinyga_options = $this->getOptions();
        $this->setClient();
    }

    private function setClient()
    {
        $this->client = new ImageOptimizerClient();

        if (defined('TINYGA_API_ENDPOINT')) {
            $this->client->setApiEndpointUrl(TINYGA_API_ENDPOINT);
        }

        if ($this->tinyga_options->getApiKey()) {
            $this->client->setApiKey($this->tinyga_options->getApiKey());
        }
    }

    /**
     * @param ImageFile $image
     * @param int|null $quality
     *
     * @return OptimizationResult
     * @throws OptimizationException
     */
    public function optimizeImage(ImageFile $image, $quality = null)
    {
        $this->last_request = $request = new OptimizationRequest($image);

        if (defined('TINYGA_TEST_MODE')) {
            $request->setTest(TINYGA_TEST_MODE);
        }

        if ($quality || $this->tinyga_options->getQuality()) {
            $request->setQuality($quality ?: $this->tinyga_options->getQuality());
        }

        set_time_limit(400);
        return $this->client->optimizeImage($request);
    }

    /**
     * @return int
     */
    public function getLastRequestQuality()
    {
        return $this->last_request->getQuality();
    }
}
