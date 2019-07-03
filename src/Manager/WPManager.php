<?php

namespace Tinyga\Manager;

use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaOptions;
use Tinyga\Model\TinygaThumbMeta;
use Tinyga\Model\WPAttachmentMeta;
use Tinyga\Tinyga;

class WPManager
{
    /**
     * @param string $tag
     * @param callback $function_to_add
     * @param int $priority
     * @param int $accepted_args
     *
     * @return bool|void
     */
    public function addAction($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        return add_action($tag, $function_to_add, $priority, $accepted_args);
    }

    /**
     * @param string $tag
     * @param callback $function_to_add
     * @param int $priority
     * @param int $accepted_args
     *
     * @return bool|void
     */
    public function addFilter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        return add_filter($tag, $function_to_add, $priority, $accepted_args);
    }

    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callback|string $function
     *
     * @return bool|void
     */
    public function addOptionsPage($page_title, $menu_title, $capability, $menu_slug, $function = '')
    {
        return add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function trans($text)
    {
        return translate($text, Tinyga::SLUG);
    }

    /**
     * @param string $message
     * @param string $title
     * @param array $args
     */
    public function WPDie($message = '', $title = '', $args = [])
    {
        wp_die($message, $title, $args);
    }

    /**
     * @return TinygaOptions
     */
    public function getTinygaOptions()
    {
        $tinyga_options = get_option(TinygaOptions::OPTION_NAME, []);

        return new TinygaOptions($tinyga_options);
    }

    /**
     * @param array|TinygaOptions $options
     *
     * @return bool
     */
    public function updateTinygaOptions($options)
    {
        if ($options instanceof TinygaOptions) {
            $options = $options->toArray();
        }

        return update_option(TinygaOptions::OPTION_NAME, $options);
    }

    /**
     * @param int $id
     *
     * @return TinygaImageMeta|null
     */
    public function getImageMeta($id)
    {
        $tinyga_image_meta = get_post_meta($id, TinygaImageMeta::OPTION_NAME, true);

        return $tinyga_image_meta ? new TinygaImageMeta($tinyga_image_meta) : null;
    }

    /**
     * @param int $id
     * @param array|TinygaImageMeta $data
     *
     * @return mixed
     */
    public function updateImageMeta($id, $data)
    {
        if ($data instanceof TinygaImageMeta) {
            $data = $data->toArray();
        }

        return update_post_meta($id, TinygaImageMeta::OPTION_NAME, $data);
    }

    /**
     * @param int $id
     * @param bool $array
     *
     * @return TinygaThumbMeta[]|array
     */
    public function getThumbsMeta($id, $array = false)
    {
        $tinyga_thumbs_meta = get_post_meta($id, TinygaThumbMeta::OPTION_NAME, true) ?: [];

        if ($array) {
            return $tinyga_thumbs_meta;
        }

        return array_map(static function ($tinyga_thumb_meta) {
            return new TinygaThumbMeta($tinyga_thumb_meta);
        }, $tinyga_thumbs_meta);
    }

    /**
     * @param int $id
     * @param array|TinygaThumbMeta $data
     *
     * @return mixed
     */
    public function updateThumbsMeta($id, $data)
    {
        if ($data instanceof TinygaThumbMeta) {
            $data = $data->toArray();
        }

        return update_post_meta($id, TinygaThumbMeta::OPTION_NAME, $data);
    }

    /**
     * @param int $id
     * @param bool $unfiltered
     *
     * @return WPAttachmentMeta|null
     */
    public function getAttachmentMeta($id, $unfiltered = false)
    {
        $wp_attachment_meta = wp_get_attachment_metadata($id, $unfiltered);

        return $wp_attachment_meta ? new WPAttachmentMeta($wp_attachment_meta) : null;
    }

    /**
     * @param int $id
     * @param string $file
     *
     * @return mixed
     */
    public function generateAttachmentMeta($id, $file)
    {
        return wp_generate_attachment_metadata($id, $file);
    }

    /**
     * @param int $id
     * @param array|WPAttachmentMeta $data
     *
     * @return bool|int
     */
    public function updateAttachmentMeta($id, $data)
    {
        if ($data instanceof WPAttachmentMeta) {
            $data = $data->toArray();
        }

        return wp_update_attachment_metadata($id, $data);
    }

    /**
     * @param int $attachment_id
     * @param bool $unfiltered
     *
     * @return string
     */
    public function getAttachedFile($attachment_id, $unfiltered = false)
    {
        return wp_normalize_path(get_attached_file($attachment_id, $unfiltered));
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function attachmentIsImage($id)
    {
        return wp_attachment_is_image($id);
    }

    /**
     * @param int $id
     *
     * @return bool|string
     */
    public function getAttachmentUrl($id)
    {
        return wp_get_attachment_url($id);
    }

    /**
     * @param string $option
     *
     * @return bool
     */
    public function deleteOption($option)
    {
        return delete_option($option);
    }

    /**
     * @param int $id
     * @param string $meta_key
     * @param mixed $meta_value
     *
     * @return bool
     */
    public function deletePostMeta($id, $meta_key, $meta_value = '')
    {
        return delete_post_meta($id, $meta_key, $meta_value);
    }

    /**
     * @param string $post_meta_key
     *
     * @return bool
     */
    public function deletePostMetaByKey($post_meta_key)
    {
        return delete_post_meta_by_key($post_meta_key);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    public function pluginBasename($file)
    {
        return plugin_basename($file);
    }

    /**
     * @param string $path
     * @param string $scheme
     *
     * @return string|void
     */
    public function adminUrl($path = '', $scheme = 'admin')
    {
        return admin_url($path, $scheme);
    }

    /**
     * @param $handle
     * @param string|bool $src
     * @param array $deps
     * @param string|bool $ver
     * @param bool $in_footer
     */
    public function enqueueScript($handle, $src = false, $deps = [], $ver = false, $in_footer = false)
    {
        $asset_path = $src ? $this->getAsset($src) : $src;
        wp_enqueue_script($handle, $asset_path, $deps, $ver, $in_footer);
    }

    /**
     * @param $handle
     * @param string|bool $src
     * @param array $deps
     * @param string|bool $ver
     * @param string $media
     */
    public function enqueueStyle($handle, $src = false, $deps = [], $ver = false, $media = 'all')
    {
        $asset_path = $src ? $this->getAsset($src) : $src;
        wp_enqueue_style($handle, $asset_path, $deps, $ver, $media);
    }

    /**
     * @param string $handle
     * @param string $object_name
     * @param array $l10n
     *
     * @return bool
     */
    public function localizeScript($handle, $object_name, $l10n)
    {
        return wp_localize_script($handle, $object_name, $l10n);
    }

    /**
     * @param $asset
     *
     * @return string
     */
    public function getAsset($asset)
    {
        return plugins_url('public/' . ltrim($asset, '/\\'), TINYGA_PLUGIN_FILE);
    }

    /**
     * @param string|null $time
     *
     * @return array
     */
    public function getUploadDir($time = null)
    {
        $wp_upload_dir = wp_upload_dir($time);

        foreach (['basedir', 'path'] as $key) {
            if (isset($wp_upload_dir[$key])) {
                $wp_upload_dir[$key] = wp_normalize_path($wp_upload_dir[$key]);
            }
        }

        return $wp_upload_dir;
    }

    /**
     * @param bool $keys_only
     *
     * @return array
     */
    public function getImageSizes($keys_only = false)
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $_size) {
            if (isset($_wp_additional_image_sizes[$_size])) {
                $width = $_wp_additional_image_sizes[$_size]['width'];
                $height = $_wp_additional_image_sizes[$_size]['height'];
                $crop = $_wp_additional_image_sizes[$_size]['crop'];
            } else {
                $width = get_option("{$_size}_size_w");
                $height = get_option("{$_size}_size_h");
                $crop = (bool)get_option("{$_size}_crop");
            }
            $sizes[$_size] = [
                'width' => $width,
                'height' => $height,
                'crop' => $crop,
            ];
        }

        return $keys_only ? array_keys($sizes) : $sizes;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function sanitizeTextField($str)
    {
        return sanitize_text_field($str);
    }
}
