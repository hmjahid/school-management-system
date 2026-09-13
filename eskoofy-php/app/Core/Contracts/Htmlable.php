<?php
declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Mirrors Illuminate\Contracts\Support\Htmlable so the port's e() helper can
 * render component slot HTML unescaped, exactly like Laravel does.
 */
interface Htmlable
{
    public function toHtml(): string;
}