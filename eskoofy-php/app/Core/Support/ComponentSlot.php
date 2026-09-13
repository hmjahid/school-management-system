<?php
declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Contracts\Htmlable;

/**
 * Wraps captured component slot HTML so Blade templates that echo {{ $slot }}
 * render the markup raw (Laravel's ComponentSlot equivalent).
 */
class ComponentSlot implements Htmlable
{
    public function __construct(protected string $html)
    {
    }

    public function toHtml(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}