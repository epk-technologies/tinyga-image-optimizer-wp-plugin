<?php

namespace Tinyga;

use Tinyga\ImageOptimizer\Image\ImageContent;
use Tinyga\ImageOptimizer\Image\ImageFile;
use Tinyga\ImageOptimizer\ImageOptimizerClient;
use Tinyga\ImageOptimizer\OptimizationException;
use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\ImageOptimizer\OptimizationResult;

class Tinyga
{
    public static $slug = 'tinyga';

	private $image_id;
	private $optimization_type;
    private $tinyga_settings;

    public function __construct()
    {
        $this->tinyga_settings = get_option('_tinyga_options');
        $this->registerActionsAndFilters();
    }

    /**
     * Add settings link to plugin page
     * @param $links
     * @param $file
     * @return array
     */
    public function pluginActionLinks($links, $file)
    {
        if (plugin_basename(TINYGA_PLUGIN_FILE) !== $file) {
            return $links;
        }

        $settings_url = admin_url('options-general.php?page=wp-tinyga');
        $settings_url_name = translate('Settings', self::$slug);

        return array_merge($links, [
            'settings' => "<a href='{$settings_url}'>{$settings_url_name}</a>",
       ]);
    }

    public function tinygaMenu()
    {
        add_options_page(
            translate('Tinyga Image Optimizer Settings', self::$slug),
            'Tinyga',
            'manage_options',
            'wp-tinyga',
            [&$this, 'tinygaSettingsPage']
      );
    }

    public function tinygaSettingsPage()
    {
        $result = [];
        if (!empty($_POST)) {
            $options = $_POST['_tinyga_options'];
            $result = $this->validateOptions($options);
            update_option('_tinyga_options', $result['valid']);
        }

        $settings = get_option('_tinyga_options');
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
	    $auto_optimize = isset($settings['auto_optimize']) ? $settings['auto_optimize'] : 1;
	    $optimize_main_image = isset($settings['optimize_main_image']) ? $settings['optimize_main_image'] : 1;
	    $quality = isset($settings['quality']) ? $settings['quality'] : 1;

        $this->view('settings', [
            'result' => $result,
            'api_key' => $api_key,
            'auto_optimize' => $auto_optimize,
            'optimize_main_image' => $optimize_main_image,
	        'quality' => $quality
       ]);
    }

    /**
     * @param $hook
     */
    public function myEnqueue($hook)
    {
        if ($hook === 'options-media.php' || $hook === 'upload.php' || $hook === 'settings_page_wp-tinyga') {
            wp_enqueue_script('jquery');
            if (TINYGA_DEV_MODE === true) {
                wp_enqueue_script('tinyga-async-js', self::asset('js/async.js'));
                wp_enqueue_script('tinyga-tipsy-js', self::asset('js/jquery.tipsy.js'), ['jquery']);
                wp_enqueue_script('tinyga-modal-js', self::asset('js/jquery.modal.min.js'), ['jquery']);
                wp_enqueue_script('tinyga-ajax-js', self::asset('js/ajax.js'), ['jquery']);
                wp_localize_script('tinyga-ajax-js', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
                wp_localize_script('tinyga-ajax-js', 'tinyga_settings', $this->tinyga_settings);
                wp_enqueue_style('tinyga-admin-css', self::asset('css/admin.css'));
                wp_enqueue_style('tinyga-tipsy-css', self::asset('css/tipsy.css'));
                wp_enqueue_style('tinyga-modal-css', self::asset('css/jquery.modal.css'));
            } else {
                wp_enqueue_script('tinyga-js', self::asset('js/dist/tinyga.min.js'), ['jquery']);
                wp_localize_script('tinyga-js', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
                wp_localize_script('tinyga-js', 'tinyga_settings', $this->tinyga_settings);
                wp_enqueue_style('tinyga-css', self::asset('css/dist/tinyga.min.css'));
            }
        }
    }

    /**
     *  Handles optimizing already-uploaded images in the  Media Library
     * @throws OptimizationException
     */
	public function tinygaMediaLibraryAjaxCallback()
    {
        $image_id = (int) $_POST['id'];
        $type = false;

        if (isset($_POST['type'])) {
            $type = $_POST['type'];
            $this->optimization_type = $type;
        }

        $this->image_id = $image_id;

        if (wp_attachment_is_image($image_id)) {

            $settings = $this->tinyga_settings;

            $image_path = $this->getAttachedFile($image_id);
            $optimize_main_image = !empty($settings['optimize_main_image']);
            $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';

            $data = [];

            if (empty($api_key)) {
                $data['error'] = 'There is a problem with your credentials. Please check them in the Tinyga settings section of Media Settings, and try again.';
                update_post_meta($image_id, '_tinyga_size', $data);
                echo json_encode(['error' => $data['error']]);
                exit;
            }

            if ($optimize_main_image) {

                // check if thumbs already optimized
                $thumbs_optimized = false;
                $tinyga_thumbs_data = get_post_meta($image_id, '_tinyga_thumbs', true);

                if (!empty($tinyga_thumbs_data)) {
                    $thumbs_optimized = true;
                }

                // get metadata for thumbnails
                $image_data = wp_get_attachment_metadata($image_id);

                if (!$thumbs_optimized) {
                    $this->optimizeThumbnails($image_data);
                } else {
                    // re-optimize thumbs if mode has changed
                    $tinyga_thumbs_mode = $tinyga_thumbs_data[0]['type'];
                    if (strcmp($tinyga_thumbs_mode, $this->optimization_type) !== 0) {
                        wp_generate_attachment_metadata($image_id, $image_path);
                        $this->optimizeThumbnails($image_data);
                    }
                }

                $resize = false;
                if (!empty($settings['resize_width']) || !empty($settings['resize_height'])) {
                    $resize = true;
                }

                $original_image = new ImageFile($image_path);

                try {
                    $optimization_result = $this->optimizeImage($original_image);
                } catch (ImageOptimizer\OptimizationException $e) {
                    // error or no optimization
                    $data['error_code'] = $e->getErrorCode();
                    $data['code'] = $e->getCode();
                    $data['message'] = $e->getMessage();

                    if (file_exists($image_path)) {
                        $data['original_size'] = filesize($image_path);
                        update_post_meta($image_id, '_tinyga_size', $data);
                    }

                    echo json_encode(['error' => $data, '']);

                    return;
                }

                $data = $this->getResultArr($optimization_result, $original_image, $image_id);
                if ($this->replaceImage($image_path, $optimization_result->getOptimizedImage())) {

                    if (!empty($data['width']) && !empty($data['height'])) {
                        /** @var array $image_data */
                        $image_data = wp_get_attachment_metadata($image_id);
                        $image_data['width'] = $data['width'];
                        $image_data['height'] = $data['height'];
                        wp_update_attachment_metadata($image_id, $image_data);
                    }

                    // store tinyga info to DB
                    update_post_meta($image_id, '_tinyga_size', $data);

                    // optimize thumbnails, store that data too. This can be unset when there are no thumbs
                    $tinyga_thumbs_data = get_post_meta($image_id, '_tinyga_thumbs', true);
                    if (!empty($tinyga_thumbs_data)) {
                        $data['thumbs_data'] = $tinyga_thumbs_data;
                        $data['success'] = true;
                    }

                    $data['html'] = $this->generateStatsSummary($image_id);
                    echo json_encode($data);

                } else {
                    echo json_encode(['error' => 'Could not overwrite original file. Please ensure that your files are writable by plugins.']);
                    exit;
                }
            } else {
                // get metadata for thumbnails
                $image_data = wp_get_attachment_metadata($image_id);
                $this->optimizeThumbnails($image_data);

                // optimize thumbnails, store that data too. This can be unset when there are no thumbs
                $tinyga_thumbs_data = get_post_meta($image_id, '_tinyga_thumbs', true);

                if (!empty($tinyga_thumbs_data)) {
                    $data['thumbs_data'] = $tinyga_thumbs_data;
                    $data['success'] = true;
                }
                $data['html'] = $this->generateStatsSummary($image_id);

                echo json_encode($data);
            }
        }
        wp_die();
    }

	/**
	 * Handles optimizing images uploaded through any of the media uploaders.
	 *
	 * @param $image_id
	 */
	public function tinygaMediaUploaderCallback($image_id)
	{
		$this->image_id = $image_id;

		if (empty($this->tinyga_settings['optimize_main_image'])) {
			return;
		}

//		if (!$this->isApiActive()) {
//			remove_filter('wp_generate_attachment_metadata', [&$this, 'optimize_thumbnails']);
//			remove_action('add_attachment', [&$this, 'tinyga_media_uploader_callback']);
//			return;
//		}

		if (wp_attachment_is_image($image_id)) {

			$image_path = $this->getAttachedFile($image_id);
			$image_backup_path = $image_path . '_tinyga_' . md5($image_path);
			$backup_created = copy($image_path, $image_backup_path);
			$original_image = new ImageFile($backup_created ? $image_backup_path : $image_path);

			try {
				$optimization_result = $this->optimizeImage($original_image);
			} catch (ImageOptimizer\OptimizationException $e) {
				// error or no optimization
				if (file_exists($image_path)) {
					$data['original_size'] = filesize($image_path);
					$data['error_code'] = $e->getErrorCode();
					$data['code'] = $e->getCode();
					$data['message'] = $e->getMessage();
					update_post_meta($image_id, '_tinyga_size', $data);
				}

				return;
			}

			$data = $this->getResultArr($optimization_result, $original_image, $image_id);

            if ($data['saved_bytes'] > 0) {
                if ($backup_created) {
                    // @todo ??? replacing backup won't affect anything in WP at this stage
                    $data['optimized_backup_file'] = $image_backup_path;
                    $image_to_replace_path = $image_backup_path;
                } else {
                    $image_to_replace_path = $image_path;
                }

                if (!$this->replaceImage($image_to_replace_path, $optimization_result->getOptimizedImage())) {
                    error_log('Tinyga: Could not replace local image with optimized image.');
                }
            }

			update_post_meta($image_id, '_tinyga_size', $data);
		}
	}

    /**
     * @param $image_data
     *
     * @return mixed
     */
    public function optimizeThumbnails($image_data)
    {
		$image_id = $this->image_id;
		if (empty($image_id)) {
			global $wpdb;
			$post = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s LIMIT 1", $image_data['file']));
			$image_id = $post->post_id;
		}

		$tinyga_meta = get_post_meta($image_id, '_tinyga_size', true);
		$image_backup_path = isset($tinyga_meta['optimized_backup_file']) ? $tinyga_meta['optimized_backup_file'] : '';

		if ($image_backup_path) {
			$original_image_path = $this->getAttachedFile($image_id);
			if (copy($image_backup_path, $original_image_path)) {
				unlink($image_backup_path);
				unset($tinyga_meta['optimized_backup_file']);
				update_post_meta($image_id, '_tinyga_size', $tinyga_meta);
			}
		}

		if (!self::pregArrayKeyExists('/^include_size_/', $this->tinyga_settings)) {

			global $_wp_additional_image_sizes;
			$sizes = [];

			foreach (get_intermediate_image_sizes() as $_size) {
				if (in_array($_size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
					$sizes[$_size]['width']  = get_option("{$_size}_size_w");
					$sizes[$_size]['height'] = get_option("{$_size}_size_h");
					$sizes[$_size]['crop']   = (bool) get_option("{$_size}_crop");
				} elseif (isset($_wp_additional_image_sizes[$_size])) {
					$sizes[$_size] = [
						'width'  => $_wp_additional_image_sizes[$_size]['width'],
						'height' => $_wp_additional_image_sizes[$_size]['height'],
						'crop'   => $_wp_additional_image_sizes[$_size]['crop'],
                    ];
				}
			}
			$sizes = array_keys($sizes);
			foreach ($sizes as $size) {
				$this->tinyga_settings['include_size_' . $size] = 1;
			}
		}

		// when resizing has taken place via API, update the post metadata accordingly
		if (!empty($tinyga_meta['tinyga_width']) && !empty($tinyga_meta['tinyga_height'])) {
			$image_data['width'] = $tinyga_meta['tinyga_width'];
			$image_data['height'] = $tinyga_meta['tinyga_height'];
		}

		$path_parts = pathinfo($image_data['file']);

		// e.g. 04/02, for use in getting correct path or URL
		$upload_subdir = $path_parts['dirname'];

		$upload_dir = $this->getWpUploadDir();

		// all the way up to /uploads
		$upload_base_path = $upload_dir['basedir'];
		$upload_full_path = $upload_base_path . '/' . $upload_subdir;

		$sizes = [];

		if (isset($image_data['sizes'])) {
			$sizes = $image_data['sizes'];
		}

		if (!empty($sizes)) {

			$sizes_to_optimize = $this->getSizesToOptimize();
			$thumbs_optimized_store = [];

			foreach ($sizes as $key => $size) {

				if (!in_array("include_size_$key", $sizes_to_optimize, false)) {
					continue;
				}

				$thumb_path = $upload_full_path . '/' . $size['file'];

				if (file_exists($thumb_path) !== false) {
                    $thumb_image = new ImageFile($thumb_path);

                    try {
                        $optimization_result = $this->optimizeImage($thumb_image);
                    } catch (ImageOptimizer\OptimizationException $e) {
                        continue;
                    }

                    $data = $this->getResultArr($optimization_result, $thumb_image);

                    if ($data['saved_bytes'] > 0) {
                        if ($this->replaceImage($thumb_path, $optimization_result->getOptimizedImage())) {
                            $this_thumb = [
                                'thumb' => $key,
                                'file' => $size['file'],
                                'original_size' => $data['original_size'],
                                'optimized_size' => $data['optimized_size'],
                                'type' => $this->optimization_type
                            ];
                            $thumbs_optimized_store [] = $this_thumb;
                        }
                    } else {
                        $this_thumb = [
                            'thumb' => $key,
                            'file' => $size['file'],
                            'original_size' => $data['original_size'],
                            'optimized_size' => $data['original_size'],
                            'type' => $this->optimization_type
                        ];
                        $thumbs_optimized_store [] = $this_thumb;
                    }
				}
			}
		}
		if (!empty($thumbs_optimized_store)) {
			update_post_meta($image_id, '_tinyga_thumbs', $thumbs_optimized_store, false);
		}
		return $image_data;
	}

	private function registerActionsAndFilters()
    {
        add_action('admin_menu', [&$this, 'tinygaMenu']);
        add_action('admin_enqueue_scripts', [&$this, 'myEnqueue']);
        add_action('wp_ajax_tinyga_request', [&$this, 'tinygaMediaLibraryAjaxCallback']);
        add_filter('plugin_action_links', [&$this, 'pluginActionLinks'], 10, 2);
        add_action('manage_media_custom_column', [&$this, 'fillMediaColumns'], 10, 2);
        add_filter('manage_media_columns', [&$this, 'addMediaColumns']);

        if (!empty($this->tinyga_settings['auto_optimize']) || !isset($this->tinyga_settings['auto_optimize'])) {
            add_action('add_attachment', [&$this, 'tinygaMediaUploaderCallback']);
            add_filter('wp_generate_attachment_metadata', [&$this, 'optimizeThumbnails']);
        }
    }

    /**
     * @param $name
     * @param array $args
     */
    private function view($name, array $args = [])
    {
//        $args = apply_filters('akismet_view_arguments', $args, $name);

        foreach ($args AS $key => $val) {
            $$key = $val;
        }

//        load_plugin_textdomain('akismet');

        $file = TINYGA_PLUGIN_DIR . 'views/'. $name . '.php';

        include($file);
    }

    /**
     * @param $input
     *
     * @return array
     */
    private function validateOptions($input)
    {
        $valid = [];
        $error = [];

        if (empty($input['api_key'])) {
            $error[] = 'API Credentials must not be left blank.';
        }

        $valid['api_key'] = $input['api_key'];
        $valid['auto_optimize'] = isset($input['auto_optimize']) ? 1 : 0;
        $valid['optimize_main_image'] = isset($input['optimize_main_image']) ? 1 : 0;
        $valid['quality'] = isset($input['quality']) ? $input['quality'] : OptimizationRequest::DEFAULT_LOSSY_QUALITY;

        if (!empty($error)) {
            return ['success' => false, 'error' => $error, 'valid' => $valid];
        }

        return ['success' => true, 'valid' => $valid];
    }

	/**
	 * @param ImageFile $image
	 *
	 * @return OptimizationResult
	 * @throws OptimizationException
	 */
	private function optimizeImage(ImageFile $image)
	{
		$settings = $this->tinyga_settings;

		$client = new ImageOptimizerClient();

		if (defined('TINYGA_API_ENDPOINT')) {
			$client->setApiEndpointUrl(TINYGA_API_ENDPOINT);
		}

		if (isset($settings['api_key'])) {
			$client->setApiKey($settings['api_key']);
		}

		$request = new OptimizationRequest($image);

		if (defined('TINYGA_TEST_MODE')) {
			$request->setTest(TINYGA_TEST_MODE);
		}

        if (isset($settings['quality'])) {
	        $request->setQuality($settings['quality']);
        }

		set_time_limit(400);
		return $client->optimizeImage($request);
	}

	/**
	 * Converts an deserialized API result array into an array which this plugin will consume
	 *
	 * @param OptimizationResult $result
	 * @param ImageFile $original_image
	 * @param int|null $image_id
	 *
	 * @return array
	 */
	private function getResultArr(OptimizationResult $result, ImageFile $original_image, $image_id = null)
	{
		$optimized_image = $result->getOptimizedImage();
		$optimized_image_params = $optimized_image->getImageParameters();
		$original_image_params = $original_image->getImageParameters();

		$rv = [];
        $rv['task_id'] = $result->getTaskId();
		$rv['original_size'] = $original_image_params->getFileSize();
		$rv['optimized_size'] = $optimized_image_params->getFileSize();
		$rv['saved_bytes'] = $rv['original_size'] - $rv['optimized_size'];
		$savings_percentage = $rv['saved_bytes'] / $rv['original_size'] * 100;
		$rv['savings_percent'] = round($savings_percentage, 2) . '%';
		$rv['type'] = $optimized_image_params->getMimeType();
		if (!empty($original_image_params->getWidth()) && !empty($original_image_params->getHeight())) {
			$rv['width'] = $original_image_params->getWidth();
			$rv['height'] = $original_image_params->getHeight();
		}
		if ($image_id) {
            $rv['meta'] = wp_get_attachment_metadata($image_id);
        }

		return $rv;
	}

	/**
	 * @param string $image_path
	 * @param $content
	 *
	 * @return bool
	 */
	private function replaceImage($image_path, ImageContent $content)
    {
		$rv = file_put_contents($image_path, $content->getContent());
		return $rv !== false;
	}

    /**
     * @param $columns
     *
     * @return mixed
     */
    public function addMediaColumns($columns)
    {
        $columns['original_size'] = 'Original Size';
        $columns['optimized_size'] = 'Tinyga Stats';
        return $columns;
    }

    /**
     * @param $column_name
     * @param $id
     */
    public function fillMediaColumns($column_name, $id)
    {
        $settings = $this->tinyga_settings;
        $optimize_main_image = !empty($settings['optimize_main_image']);

        $file = $this->getAttachedFile($id);
        $original_size = filesize($file);

        // handle the case where file does not exist
        if ($original_size === 0 || $original_size === false) {
            echo '0 bytes';
            return;
        }

        $original_size = self::formatBytes($original_size);

        $type = isset($settings['api_lossy']) ? $settings['api_lossy'] : 'lossy';

        if (strcmp($column_name, 'original_size') === 0) {
            if (wp_attachment_is_image($id)) {

                $meta = get_post_meta($id, '_tinyga_size', true);

                if (isset($meta['original_size'])) {
                    if (stripos($meta['original_size'], 'kb') !== false) {
                        echo self::formatBytes(ceil((float)$meta['original_size'] * 1024));
                    } else {
                        echo self::formatBytes($meta['original_size']);
                    }
                } else {
                    echo $original_size;
                }
            } else {
                echo $original_size;
            }
        } else if (strcmp($column_name, 'optimized_size') === 0) {
            echo '<div class="tinyga-wrap">';
            $image_url = wp_get_attachment_url($id);
            $filename = basename($image_url);
            if (wp_attachment_is_image($id)) {

                $meta = get_post_meta($id, '_tinyga_size', true);
                $thumbs_meta = get_post_meta($id, '_tinyga_thumbs', true);

                // Is it optimized? Show some stats
                if (!empty($thumbs_meta) || (isset($meta['optimized_size']) && empty($meta['no_savings']))) {
                    if (!isset($meta['optimized_size']) && $optimize_main_image) {
                        echo '<div class="buttonWrap"><button data-setting="' . $type . '" type="button" class="tinyga_req" data-id="' . $id . '" id="tinygaid-' . $id .'" data-filename="' . $filename . '" data-url="' . $image_url . '">Optimize Main Image</button><span class="tinygaSpinner"></span></div>';
                    }
                    echo $this->generateStatsSummary($id);

                    // Were there no savings, or was there an error?
                } else {
                    echo '<div class="buttonWrap"><button data-setting="' . $type . '" type="button" class="tinyga_req" data-id="' . $id . '" id="tinygaid-' . $id .'" data-filename="' . $filename . '" data-url="' . $image_url . '">Optimize This Image</button><span class="tinygaSpinner"></span></div>';
                    if (!empty($meta['no_savings'])) {
                        echo '<div class="noSavings"><strong>No savings found</strong><br /><small>Type:&nbsp;' . $meta['type'] . '</small></div>';
                    } else if (isset($meta['error'])) {
                        $error = $meta['error'];
                        echo '<div class="tinygaErrorWrap"><a class="tinygaError" title="' . $error . '">Failed! Hover here</a></div>';
                    }
                }
            } else {
                echo 'n/a';
            }
            echo '</div>';
        }
    }

    /**
     * @param $size
     * @param int $precision
     *
     * @return string
     */
    private static function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = [' bytes', 'KB', 'MB', 'GB', 'TB'];
        return round(1024 ** ($base - floor($base)), $precision) . $suffixes[(int)floor($base)];
    }

    /**
     * @param $id
     *
     * @return string
     */
    private function generateStatsSummary($id)
    {
        $image_meta = get_post_meta($id, '_tinyga_size', true);
        $thumbs_meta = get_post_meta($id, '_tinyga_thumbs', true);

        $total_original_size = 0;
        $total_saved_bytes = 0;

        // crap for backward compat
        if (isset($image_meta['original_size'])) {

            $original_size = $image_meta['original_size'];

            if (stripos($original_size, 'kb') !== false) {
                $total_original_size = ceil((float)$original_size * 1024);
            } else {
                $total_original_size = (int)$original_size;
            }

            if (isset($image_meta['saved_bytes'])) {
                $saved_bytes = $image_meta['saved_bytes'];
                if (is_string($saved_bytes)) {
                    $total_saved_bytes = (int)ceil((float)$saved_bytes * 1024);
                } else {
                    $total_saved_bytes = $saved_bytes;
                }
            }
        }

        if (!empty($thumbs_meta)) {
            foreach ($thumbs_meta as $k => $v) {
                $total_original_size += $v['original_size'];
                $total_saved_bytes += $v['original_size'] - $v['optimized_size'];
            }

        }

        $total_savings_percentage = round($total_saved_bytes / $total_original_size * 100, 2) . '%';
        if (!$total_saved_bytes) {
            $summary_string = 'No savings';
        } else {
            $total_savings = self::formatBytes($total_saved_bytes);
            $detailed_results_html = $this->resultsHtml($id);
            $summary_string = '<div class="tinyga-result-wrap">' . "Saved $total_savings_percentage ($total_savings)";
            $summary_string .= '<br /><small class="tinyga-item-details" data-id="' . $id . '" original-title="' . htmlspecialchars($detailed_results_html) .'">Show details</small></div>';
        }

        return $summary_string;
    }

    /**
     * @param $id
     *
     * @return false|string
     */
    private function resultsHtml($id)
    {
        // get meta data for main post and thumbs
        $image_meta = get_post_meta($id, '_tinyga_size', true);
        $thumbs_meta = get_post_meta($id, '_tinyga_thumbs', true);
        $main_image_optimized = !empty($image_meta) && isset($image_meta['type']);
        $thumbs_optimized = !empty($thumbs_meta) && count($thumbs_meta) && isset($thumbs_meta[0]['type']);

        $type = '';

        if ($main_image_optimized) {
            $type = $image_meta['type'];
            $main_image_tinyga_stats = self::calculateSavings($image_meta);
        }

        if ($thumbs_optimized) {
            $type = $thumbs_meta[0]['type'];
            $thumbs_tinyga_stats = self::calculateSavings($thumbs_meta);
            $thumbs_count = count($thumbs_meta);
        }

        ob_start();
        ?>
        <?php if ($main_image_optimized) { ?>
            <div class="tinyga_detailed_results_wrap">
            <span class=""><strong>Main image savings:</strong></span>
            <br />
            <span style="display:inline-block;margin-bottom:5px"><?php echo $main_image_tinyga_stats['saved_bytes']; ?> (<?php echo $main_image_tinyga_stats['savings_percentage']; ?> saved)</span>
        <?php } ?>
        <?php if ($main_image_optimized && $thumbs_optimized) { ?>
            <br />
        <?php } ?>
        <?php if ($thumbs_optimized) { ?>
            <span><strong>Savings on <?php echo $thumbs_count; ?> thumbnails:</strong></span>
            <br />
            <span style="display:inline-block;margin-bottom:5px"><?php echo $thumbs_tinyga_stats['total_savings']; ?> (<?php echo $thumbs_tinyga_stats['savings_percentage']; ?> saved)</span>
        <?php } ?>
        <br />
        <span><strong>Optimization mode:</strong></span>
        <br />
        <span><?php echo ucfirst($type); ?></span>
        <?php if (!empty($this->tinyga_settings['show_reset'])) { ?>
            <br />
            <small
                class="tinygaReset" data-id="<?php echo $id; ?>"
                title="Removes Tinyga metadata associated with this image">
                Reset
            </small>
            <span class="tinygaSpinner"></span>
            </div>
        <?php } ?>
        <?php
        return ob_get_clean();
    }

    /**
     * @param $meta
     *
     * @return array
     */
    private static function calculateSavings($meta)
    {
        if (isset($meta['original_size'])) {

            $saved_bytes = isset($meta['saved_bytes']) ? $meta['saved_bytes'] : '';
            $savings_percentage = $meta['savings_percent'];

            // convert old data format, where applicable
            if (stripos($saved_bytes, 'kb') !== false) {
                $saved_bytes = self::KBStringToBytes($saved_bytes);
            } else {
                if (!$saved_bytes) {
                    $saved_bytes = '0 bytes';
                } else {
                    $saved_bytes = self::formatBytes($saved_bytes);
                }
            }

            return [
                'saved_bytes' => $saved_bytes,
                'savings_percentage' => $savings_percentage
            ];

        }

        if (!empty($meta)) {
            $total_thumb_byte_savings = 0;
            $total_thumb_size = 0;

            foreach ($meta as $k => $v) {
                $total_thumb_size += $v['original_size'];
                $thumb_byte_savings = $v['original_size'] - $v['optimized_size'];
                $total_thumb_byte_savings += $thumb_byte_savings;
            }

            $thumbs_savings_percentage = round($total_thumb_byte_savings / $total_thumb_size * 100, 2) . '%';
            if ($total_thumb_byte_savings) {
                $total_thumbs_savings = self::formatBytes($total_thumb_byte_savings);
            } else {
                $total_thumbs_savings = '0 bytes';
            }
            return [
                'savings_percentage' => $thumbs_savings_percentage,
                'total_savings' => $total_thumbs_savings
            ];
        }

        return [];
    }

    /**
     * @param $str
     *
     * @return string
     */
    private static function KBStringToBytes($str)
    {
        $rv = (float)0 === (float)$str
            ? '0 bytes'
            : self::formatBytes(ceil((float)$str * 1024));
        return $rv;
    }

    /**
     * @param $asset
     *
     * @return string
     */
    public static function asset($asset)
    {
        return plugins_url('public/' . ltrim($asset, '/\\'), TINYGA_PLUGIN_FILE);
    }

    /**
     * @param $pattern
     * @param $array
     *
     * @return int
     */
    private static function pregArrayKeyExists($pattern, $array)
    {
        $keys = array_keys($array);
        return (int) preg_grep($pattern, $keys);
    }

    /**
     * @return array
     */
    private function getSizesToOptimize()
    {
        $settings = $this->tinyga_settings;
        $rv = [];

        foreach($settings as $key => $value) {
            if (!empty($value) && strpos($key, 'include_size') === 0) {
                $rv[] = $key;
            }
        }
        return $rv;
    }

    /**
     * @param $attachment_id
     * @param bool $unfiltered
     *
     * @return string
     */
    private function getAttachedFile($attachment_id, $unfiltered = false)
    {
        return wp_normalize_path(get_attached_file($attachment_id, $unfiltered));
    }

    /**
     * @param null $time
     *
     * @return array
     */
    private function getWpUploadDir($time = null)
    {
        $wp_upload_dir = wp_upload_dir($time);

        foreach (['basedir', 'path'] as $key) {
            if (isset($wp_upload_dir[$key])) {
                $wp_upload_dir[$key] = wp_normalize_path($wp_upload_dir[$key]);
            }
        }

        return $wp_upload_dir;
    }
}
