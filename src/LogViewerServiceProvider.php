<?php

namespace OpenAdmin\Admin\LogViewer;

use Illuminate\Support\ServiceProvider;

class LogViewerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'open-admin-logs');

        LogViewer::boot();
    }
}
