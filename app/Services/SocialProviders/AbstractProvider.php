<?php
namespace App\Services\SocialProviders;

use App\Models\EmailAddress;
use App\Models\LinkedAccount;
use App\Models\SocialProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

abstract class AbstractProvider
{
    protected string $name;
    protected string $code;
    protected string $socialiteProviderCode;

    protected bool $supportsAuth = false;

    public function __construct(protected ?SocialProvider $provider = null, protected ?string $redirectUrl = null)
    {
        $this->resolveRedirectUrl();
    }

    public function configMapping(): array
    {
        return [
            'client_id' => (object)[
                'name' => 'Client ID',
                'validation' => 'required|string',
            ],
            'client_secret' => (object)[
                'name' => 'Client Secret',
                'validation' => 'required|string',
            ],
        ];
    }

    public function install(): SocialProvider
    {
        if ($this->provider) {
            return $this->provider;
        }

        $provider = SocialProvider::whereCode($this->socialiteProviderCode)->first();
        if ($provider) {
            return $provider;
        }

        $provider = new SocialProvider();
        $provider->name = $this->name;
        $provider->code = $this->code;
        $provider->provider_class = get_called_class();
        $provider->supports_auth = $this->supportsAuth;
        $provider->enabled = false;
        $provider->auth_enabled = false;
        $provider->save();
        return $provider;
    }

    protected function getSocialiteProvider()
    {
        return Socialite::driver($this->socialiteProviderCode);
    }

    public function redirect()
    {
        return $this->getSocialiteProvider()->redirect();
    }

    protected function resolveRedirectUrl(?string $redirectUrl = null): void
    {
        if ($this->redirectUrl !== null) {
            return;
        }
        if (Auth::guest() && $this->provider->auth_enabled) {
            // Probably login
            $this->redirectUrl = route('login.return', $this->code);
        } else {
            $this->redirectUrl = route('accounts.return', $this->code);
        }
    }


    public function user(?User $localUser = null)
    {
        $remoteUser = $this->getSocialiteProvider()->user();

        DB::transaction(function() use ($localUser, $remoteUser) {
            // Find the account
            $account = $this->provider->accounts()->whereExternalId($remoteUser->getId())->first();
            if ($account && ($localUser === null || $localUser->id != $account->user_id)) {
                throw new \Exception('Account is already associated with another user');
            }

            // Find the email
            $email = EmailAddress::whereEmail($remoteUser->getEmail())->first();
            if ($email && ($localUser === null || $localUser->id !== $email->user_id)) {
                if ($email->verified_at !== null) {
                    throw new \Exception('Account is already associated with another user');
                } else {
                    // Unverified email, let's delete it
                    $email->delete();
                }
            }

            if ($localUser === null) {
                if ($account) {
                    $localUser = $account->user;
                } elseif (!$this->provider->auth_enabled) {
                    throw new \Exception('Unable to login with this account');
                } else {
                    $localUser = new User;
                    $localUser->nickname = $remoteUser->getNickname();
                    $localUser->save();
                }
            }

            if ($account === null) {
                $account = new LinkedAccount;
                $account->provider()->associate($this->provider);
                $account->user()->associate($localUser);
                $account->external_id = $remoteUser->getId();
                $account->save();
            }

            $this->updateAccount($account, $remoteUser);

            $remoteEmail = $remoteUser->getEmail();
            if ($remoteEmail !== null) {
                $email = EmailAddress::whereEmail($remoteEmail)->first();
                if ($email === null) {
                    $email = new EmailAddress();
                    $email->email = $remoteEmail;
                    $email->verified_at = Carbon::now();
                    $email->user()->associate($localUser);
                    $email->save();
                }
                $account->email()->associate($email);
                if (!$localUser->primaryEmail) {
                    $localUser->primaryEmail()->associate($email);
                }
            }

            $account->save();
            $localUser->save();
        });

        if ($localUser === null) {
            $localUser = $this->provider->accounts()->whereExternalId($remoteUser->getId())->with('user')->first()->user;
        }
        return $localUser;
    }
}
