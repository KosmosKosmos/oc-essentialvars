<?php namespace KosmosKosmos\EssentialVars;

use App;
use Lang;
use View;
use Event;
use Cache;
use Config;
use Cms\Classes\Theme;
use System\Classes\PluginBase;
use Backend\Models\BrandSetting;

class Plugin extends PluginBase {

    public function pluginDetails() {
        return [
            'name'        => 'EssentialVars',
            'description' => 'Adds the app_[url|logo|favicon|name|debug|description] and theme variables to Mail & CMS templates',
            'author'      => 'KosmosKosmos',
            'icon'        => 'icon-code',
        ];
    }

    public function boot() {
        $themePath = themes_path(Theme::getActiveThemeCode().'/lang');
        if (is_dir($themePath)) {
            Lang::addNamespace('theme', $themePath);
        }

        App::before(function () {
            Event::listen('mailer.beforeAddContent', function () {
                $appVars = $this->getAppVars();
                foreach ($appVars as $key => $appVar) {
                    View::share($key, $appVar);
                }
            });

            Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) {
                $appVars = $this->getAppVars();
                foreach ($appVars as $key => $appVar) {
                    $controller->vars[$key] = $appVar;
                }
            });
        });
    }

    public function getAppVars() {
        return Cache::remember('essentialvars', now()->addMinutes(1), function() {
            $vars = [
                'app_url' => url('/'),
                'app_logo' => BrandSetting::getLogo() ?? url('/modules/backend/assets/images/october-logo.svg'),
                'app_favicon' => BrandSetting::getFavicon() ?? url('/modules/backend/assets/images/favicon.png'),
                'app_name' => BrandSetting::get('app_name'),
                'app_debug' => Config::get('app.debug', false),
                'app_description' => BrandSetting::get('app_tagline'),
                'hasCookieGroups' => false
            ];
            if (class_exists('\OFFLINE\GDPR\Models\CookieGroup')) {
                $vars['hasCookieGroups'] = \OFFLINE\GDPR\Models\CookieGroup::with('cookies')
                        ->orderBy('sort_order', 'ASC')
                        ->count() > 0;
            }
            if ($activeTheme = \Cms\Classes\Theme::getActiveTheme()) {
                $notInclude = ['theme' => 1, 'id' => 1, 'data' => 1, 'created_at' => 1, 'updated_at' => 1];
                $themeVars = array_diff_key($activeTheme->getCustomData()->attributes, $notInclude);
                foreach ($themeVars as $key => $value) {
                    $vars['theme_' . $key] = $value;
                }
            }
            $vars['available_vars'] = $vars;
            return $vars;
        });
    }
}
