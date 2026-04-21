<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Controllers\LanguageController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class P3ControllerUtilitiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        session()->start();
    }

    #[Test]
    public function base_controller_helpers_return_expected_view_and_redirect_responses(): void
    {
        $controller = new class extends BaseController
        {
            public function exposeView(string $view, array $data = []): View
            {
                return $this->view($view, $data);
            }

            public function exposeRedirectSuccess(string $route, string $message): RedirectResponse
            {
                return $this->redirectWithSuccess($route, $message);
            }

            public function exposeRedirectError(string $route, string $message): RedirectResponse
            {
                return $this->redirectWithError($route, $message);
            }
        };

        $view = $controller->exposeView('welcome', ['foo' => 'bar']);
        $this->assertSame('welcome', $view->name());
        $this->assertSame('bar', $view->getData()['foo']);

        $success = $controller->exposeRedirectSuccess('home', 'ok');
        $this->assertSame(route('home'), $success->getTargetUrl());
        $this->assertSame('ok', session('success'));

        $error = $controller->exposeRedirectError('home', 'nope');
        $this->assertSame(route('home'), $error->getTargetUrl());
        $this->assertSame('nope', session('error'));
    }

    #[Test]
    public function language_controller_switch_stores_supported_locale_and_redirects_back(): void
    {
        config([
            'app.supported_locales' => ['en', 'ar'],
            'app.fallback_locale' => 'en',
        ]);

        $request = Request::create('/locale/ar', 'GET', [], [], [], [
            'HTTP_REFERER' => 'http://localhost/previous',
        ]);

        $response = app(LanguageController::class)->switch($request, 'ar');

        $this->assertSame('ar', session('locale'));
        $this->assertSame('ar', app()->getLocale());
        $this->assertTrue($response->isRedirection());
        $this->assertNotSame('', $response->getTargetUrl());
    }

    #[Test]
    public function language_controller_switch_falls_back_when_locale_is_not_supported(): void
    {
        config([
            'app.supported_locales' => ['en', 'ar'],
            'app.fallback_locale' => 'en',
        ]);

        $request = Request::create('/locale/fr', 'GET', [], [], [], [
            'HTTP_REFERER' => 'http://localhost/somewhere',
        ]);

        $response = app(LanguageController::class)->switch($request, 'fr');

        $this->assertSame('en', session('locale'));
        $this->assertSame('en', app()->getLocale());
        $this->assertTrue($response->isRedirection());
        $this->assertNotSame('', $response->getTargetUrl());
    }
}
