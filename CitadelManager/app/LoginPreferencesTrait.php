<?php

namespace App;

trait LoginPreferencesTrait
{
    protected $defaultLoginPreferencesName = 'login_preferences';

    public function is2FAAuthEnabled() {
        if(method_exists($this, "getConfigValue")) {
            return $this->getConfigValue("two_factor_email_enabled") ?? false;
        } else {
            $prefAttributeName = $this->getLoginPreferencesAttributeName();
            return $this->{$prefAttributeName}['two_factor_email_enabled'] ?? false;
        }
    }

    public function isPasswordAuthEnabled() {
        if(method_exists($this, "getConfigValue")) {
            return $this->getConfigValue("password_login_enabled") ?? true;
        } else {
            $prefAttributeName = $this->getLoginPreferencesAttributeName();
            return $this->{$prefAttributeName}['password_login_enabled'] ?? true;
        }
    }

    public function getTwoFAAuthEnabledAttribute()
    {
        return $this->is2FAAuthEnabled();
    }

    public function setTwoFAAuthEnabledAttribute($value)
    {
        if(method_exists($this, "setConfigValue")) {
            $this->setConfigValue("two_factor_email_enabled", $value);
        } else {
            $prefAttributeName = $this->getLoginPreferencesAttributeName();
            $this->{$prefAttributeName}['two_factor_email_enabled'] = $value;
        }
    }

    public function getPasswordAuthEnabledAttribute()
    {
        return $this->isPasswordAuthEnabled();
    }

    public function setPasswordAuthEnabledAttribute($value)
    {
        if(method_exists($this, "setConfigValue")) {
            $this->setConfigValue("password_login_enabled", $value);
        } else {
            $prefAttributeName = $this->getLoginPreferencesAttributeName();
            $this->{$prefAttributeName}['password_login_enabled'] = $value;
        }
    }

    private function getLoginPreferencesAttributeName()
    {
         return $this->loginPreferencesName ?? $this->defaultLoginPreferencesName;
    }
}
