<?php

use GuzzleHttp\Exception\ServerException;

class MediaController extends BaseController
{
    public function get($media)
    {
        if ($media === 'check.txt') {
            echo 'check passed';
            exit();
        }
        
        if (!($url = get_transient('cmpmd_' . $media))) {
            return;
        }

        $gourl = preg_replace('/^\/\//', 'http://', $url['data']);

        $url_data = parse_url($gourl);
        if (stristr($url_data['host'], 'komparu')) {
            $client = new GuzzleHttp\Client([
                'defaults' => ['headers' => ['User-Agent' => $_SERVER['HTTP_USER_AGENT']]]
            ]);

            $gourl .= (parse_url($gourl, PHP_URL_QUERY) ? '&' : '?')
                    . 'sid=' . SessionHelper::getSid($this->plugin)
                    . '&ip=' . $_SERVER['REMOTE_ADDR'];

            try {
                $response = $client->get($gourl, ['allow_redirects' => false]);
            } catch(ServerException $serverException) {
                (new AdminController($this->plugin))->clear();
                wp_redirect($url['page']);
                exit;
            }

            if ($real_url = $response->getHeader('Location')) {
                wp_redirect($real_url);
                exit();
            }

            if (stristr($response->getHeader('Content-type'), 'text/html')) {
                echo $response->getBody();
                exit();
            }

            $filename = wp_upload_dir()['basedir'] . '/compmodule/' . $media;
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0777, true);
            }

            if (!file_put_contents($filename, $response->getBody())) {
                return;
            }

            $filetype = wp_check_filetype($filename);

            $attachment = [
                'guid'           => wp_upload_dir()['baseurl'] . '/compmodule/' . $media,
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_mime_type' => $filetype['type'],
                'post_status'    => 'inherit'
            ];

            if (!($attach_id = wp_insert_attachment($attachment, $filename))) {
                return;
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);

            wp_redirect(wp_upload_dir()['baseurl'] . '/compmodule/' . $media);
            exit();
        } else {
            wp_redirect($url);
            exit();
        }
    }

    public static function checkForSlashes($url)
    {
        return preg_replace('/^\/\//', 'http://', $url);
    }

    public static function saveTransient($key, $data)
    {
        set_transient('cmpmd_' . $key, ['data' => $data, 'page' => $_SERVER['REQUEST_URI']], DAY_IN_SECONDS);
    }
}