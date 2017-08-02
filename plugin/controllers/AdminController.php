<?php

class AdminController extends BaseController
{
    public function index()
    {
        $websites = array_filter(
            KomparuClient
                ::getInstance($this->plugin)
                ->resource('website')
                ->get(['order' => 'product_type.id', 'with' => 'productType']),
            function ($website) {
                return $website['product_type']['implementation'] == 'komparu';
            }
        );

        return $this->view->render('admin/index', [
            'websites' => $websites
        ]);
    }

    public function settings()
    {
        ob_start();
        $this->plugin->settings->show_forms();

        return $this->view->render('admin/configure', [
            'forms' => ob_get_clean()
        ]);
    }

    public function clear($token = '')
    {
        $this->clearCache($token);

        if ($token != '') {
            (new GuzzleHttp\Client())->get(sprintf(
                'http://code.komparu.%s/%s?__reset=&format=plugin',
                $this->plugin->config['target'],
                $token
            ));
        }
    }

    public function delete()
    {
        add_filter('posts_where', function ($where) {
            return $where . ' AND (' . $GLOBALS['wpdb']->posts . '.guid LIKE "%uploads/compmodule%") ';
        }, 10, 2);

        $q = new WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 9999,
        ]);

        while ($attachment = $q->next_post()) {
            wp_delete_attachment($attachment->ID);
            $filename = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $attachment->guid);
            if (file_exists($filename)) {
                try {
                    unlink($filename);
                } catch(Exception $e) {
                }
            }
        }

        $this->clearCache();
    }

    protected function clearCache($token = '')
    {
        /** @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];
        $wpdb->get_results(sprintf(
            'DELETE FROM `%soptions` WHERE `option_name` LIKE "%%cmpmd%%%s%%"',
            $GLOBALS['wpdb']->prefix,
            $token
        ));
    }
}