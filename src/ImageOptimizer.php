<?php

namespace Tinyga;

use Tinyga\ImageOptimizer\Image\ImageFile;
use Tinyga\ImageOptimizer\ImageOptimizerClient;
use Tinyga\ImageOptimizer\OptimizationException;
use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\ImageOptimizer\OptimizationResult;

final class ImageOptimizer extends Settings
{
    const MENU_SLUG = 'wp-tinyga';

    /**
     * @var ImageOptimizerClient
     */
    private $client;

    /**
     * Register action to event.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setClient();
    }

    private function setClient()
    {
        $this->client = new ImageOptimizerClient();

        if (defined('TINYGA_API_ENDPOINT')) {
            $this->client->setApiEndpointUrl(TINYGA_API_ENDPOINT);
        }

        if (isset($this->settings[self::TINYGA_OPTIONS_API_KEY])) {
            $this->client->setApiKey($this->settings[self::TINYGA_OPTIONS_API_KEY]);
        }
    }

    /**
     * @param ImageFile $image
     *
     * @return OptimizationResult
     * @throws OptimizationException
     */
    public function optimizeImage(ImageFile $image)
    {
        $request = new OptimizationRequest($image);

        if (defined('TINYGA_TEST_MODE')) {
            $request->setTest(TINYGA_TEST_MODE);
        }

        if (isset($this->settings[self::TINYGA_OPTIONS_QUALITY])) {
            $request->setQuality($this->settings[self::TINYGA_OPTIONS_QUALITY]);
        }

        set_time_limit(400);
        return $this->client->optimizeImage($request);
    }
}
