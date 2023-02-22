<?php declare(strict_types=1);

namespace SilverStripe\Raygun;

trait CustomAppKeyProvider
{
    /**
     * Use a custom APP Key instead of the globally defined SS_RAYGUN_APP_KEY
     */
    private ?string $customRaygunAppKey = null;

    /**
     * Set a custom raygun app key that should be used instead of the
     * global default
     */
    public function setCustomRaygunAppKey(string $key): self
    {
        $this->customRaygunAppKey = $key;

        return $this;
    }

    /**
     * Returns the custom raygun app key if set
     */
    public function getCustomRaygunAppKey(): ?string
    {
        return $this->customRaygunAppKey;
    }
}
