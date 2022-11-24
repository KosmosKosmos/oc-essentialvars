<?php namespace KosmosKosmos\EssentialVars;

use App;
use Lang;
use View;
use Event;
use Config;
use System\Classes\PluginBase;
use Backend\Models\BrandSetting;

/**
 * EssentialVars Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'EssentialVars',
            'description' => 'Adds the app_[url|logo|favicon|name|debug|description] variables to Mail & CMS templates',
            'author'      => 'KosmosKosmos',
            'icon'        => 'icon-code',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        App::before(function () {
            // Share the variables with the mail template system
            Event::listen('mailer.beforeAddContent', function () {
                $appVars = [
                    'url'         => url('/'),
                    'logo'        => BrandSetting::getLogo() ?: url('/modules/backend/assets/images/october-logo.svg'),
                    'favicon'     => BrandSetting::getFavicon() ?: url('/modules/backend/assets/images/favicon.png'),
                    'name'        => BrandSetting::get('app_name'),
                    'debug'       => Config::get('app.debug', false),
                    'description' => BrandSetting::get('app_tagline'),
                ];

                View::share('app_url', $appVars['url']);
                View::share('app_logo', $appVars['logo']);
                View::share('app_favicon', $appVars['favicon']);
                View::share('app_name', $appVars['name']);
                View::share('app_debug', $appVars['debug']);
                View::share('app_description', $appVars['description']);
            });


            // Share the variables with the CMS template system
            Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) {
                $hasCookieGroups = false;
                if (class_exists('\OFFLINE\GDPR\Models\CookieGroup')) {
                    $hasCookieGroups = \OFFLINE\GDPR\Models\CookieGroup::with('cookies')->orderBy('sort_order', 'ASC')->count() > 0;
                }

                $appVars = [
                    'url'         => url('/'),
                    'logo'        => BrandSetting::getLogo() ?: url('/modules/backend/assets/images/october-logo.svg'),
                    'favicon'     => BrandSetting::getFavicon() ?: url('/modules/backend/assets/images/favicon.png'),
                    'name'        => BrandSetting::get('app_name'),
                    'debug'       => Config::get('app.debug', false),
                    'description' => BrandSetting::get('app_tagline'),
                    'hasCookieGroups' => $hasCookieGroups
                ];

                $controller->vars['app_url']         = $appVars['url'];
                $controller->vars['app_logo']        = $appVars['logo'];
                $controller->vars['app_favicon']     = $appVars['favicon'];
                $controller->vars['app_name']        = $appVars['name'];
                $controller->vars['app_debug']       = $appVars['debug'];
                $controller->vars['app_description'] = $appVars['description'];
                $controller->vars['has_cookie_groups'] = $appVars['hasCookieGroups'];
            });
        });
    }
}
