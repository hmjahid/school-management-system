<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Services\I18n;

class LanguageController extends Controller
{
    public function switch(string $locale): void
    {
        if (!I18n::switch($locale)) {
            $this->withError('Unsupported language.');
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }
}