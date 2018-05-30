<?php

use Phalcon\Mvc\User\Component;

class ProjectThemeComponent extends Component
{
    private $projectThemeDetails;
    private $refresh = 0;
    private $baseCss = [
        'leaflet/L.Icon.Pulse.css',
        'project/45/css/swiper.min.css',
        'jqweui/lib/weui.min.css',
        'plugins/pannellum.css',
    ];

    private $baseJs = [
        'js/jquery-1.9.1.min.js',
        'js/swiper.min.js',
        'leaflet/L.Icon.Pulse.js',
        'leaflet/leaflet.polylineDecorator.js',
        'leaflet/MovingMarker.js',
        'socket.io/socket.io.js',
        'jqweui/js/jquery-weui.min.js',
        'js/jquery.cookie.js',
        'js/jweixin-1.2.0.js',
    ];

    public function __construct($user)
    {
        if (!empty($user['project_id']) && !empty($user['tpl'])) {
            $projectThemeModel = new ProjectThemeModel();
            $projectThemeDetails = $projectThemeModel->getDetailsByProjectIdAndTplSimple($user['project_id'],
                $user['tpl']);
            if ($projectThemeDetails) {
                $this->projectThemeDetails = $projectThemeDetails;
            }
            $this->refresh = $user['project_front_cache'];
        }

        switch (@$user['project_id']) {
            case '76':
            case '75':
            case '74':
            case '73':
                $this->baseCss = array_merge(['leaflet/leaflet1.0.3.css','jqweui/css/fixToast.css'], $this->baseCss);
                $this->baseJs = array_merge(['leaflet/leaflet1.0.3.js'], $this->baseJs);
                break;
            default:
                $this->baseCss = array_merge(['leaflet/leaflet.css','jqweui/css/jquery-weui.min.css'], $this->baseCss);
                $this->baseJs = array_merge(['leaflet/leaflet.js'], $this->baseJs);
                break;
        }
    }

    public function getCss()
    {
        $return = '';
        $time = '';
        if ($this->refresh == '0') {
            $time = time();
        }




        $staticUrl = rtrim($this->url->getStaticBaseUri(), '/');
        foreach ($this->baseCss as $v) {
            $return .= sprintf('<link rel="stylesheet" type="text/css" href="%s/%s?%s">', $staticUrl, $v, $time);
        }

        if (!empty($this->projectThemeDetails['project_theme_css'])) {
            $return .= sprintf('<link rel="stylesheet" type="text/css" href="%s?%s">',
                $this->projectThemeDetails['project_theme_css'], $time);
        }

        return $return;
    }

    public function getJs()
    {
        $return = '';
        if (empty($this->projectThemeDetails)) {
            return $return;
        }

        if (!empty($this->projectThemeDetails['project_theme_js'])) {
            $time = '';
            if ($this->refresh == '0') {
                $time = time();
            }
            $return .= sprintf('<script type="text/javascript" src="%s?%s"></script>',
                $this->projectThemeDetails['project_theme_js'], $time);
        }
        return $return;
    }

    public function getBaseJs()
    {
        $return = '';
        if (empty($this->baseJs)) {
            return $return;
        }
        $time = '';
        if ($this->refresh == '0') {
            $time = time();
        }

        $staticUrl = rtrim($this->url->getStaticBaseUri(), '/');
        foreach ($this->baseJs as $v) {
            $return .= sprintf('<script type="text/javascript" src="%s/%s?%s"></script>', $staticUrl, $v, $time);
        }
        return $return;
    }
}