<?php

namespace App\Providers;

use App\Contracts\CartStore;
use App\Models\Setting;
use App\Support\Cart\SessionCartStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\Factory as ViewFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CartStore::class, SessionCartStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var ViewFactory $view */
        $view = view();
        $view->addLocation(resource_path());

        $this->configureDefaults();
        $this->configureFromDatabase();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureFromDatabase(): void
    {
        try {
            $stripeKey = Setting::get('stripe.secret_key');
            if ($stripeKey) {
                config(['services.stripe.key' => $stripeKey]);
            }

            $stripePubKey = Setting::get('stripe.publishable_key');
            if ($stripePubKey) {
                config(['services.stripe.publishable_key' => $stripePubKey]);
            }

            $smtpHost = Setting::get('mail.smtp_host');
            if ($smtpHost) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $smtpHost,
                    'mail.mailers.smtp.port' => Setting::get('mail.smtp_port', '587'),
                    'mail.mailers.smtp.username' => Setting::get('mail.smtp_user'),
                    'mail.mailers.smtp.password' => Setting::get('mail.smtp_password'),
                ]);
            }

            $smtpUser = Setting::get('mail.smtp_user');
            if ($smtpUser) {
                config(['mail.from.address' => $smtpUser]);
            } elseif ($shopEmail = Setting::get('shop.email')) {
                config(['mail.from.address' => $shopEmail]);
            }

            $shopName = Setting::get('shop.name');
            if ($shopName) {
                config(['mail.from.name' => $shopName]);
            }
        } catch (\Throwable) {
            // DB not yet available (e.g. during migrations)
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
