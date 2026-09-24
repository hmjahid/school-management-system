<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Page;
use App\Services\I18n;
use App\Services\ProductRecommender;

class ChooseController extends Controller
{
    public function show(): void
    {
        $this->view('site.choose', [
            'cmsPage'      => $this->cmsPage('/choose'),
            'sizes'        => ['small', 'medium', 'large'],
            'comforts'     => ['non_technical', 'some', 'technical'],
            'hostings'     => ['shared', 'vps', 'cloud', 'wordpress'],
            'stacks'       => ['any', 'php', 'javascript'],
            'priorities'   => ['budget', 'features', 'control'],
        ]);
    }

    public function result(): void
    {
        $validator = $this->validate([
            'size'     => 'required|in:small,medium,large',
            'comfort'  => 'required|in:non_technical,some,technical',
            'hosting'  => 'required|in:shared,vps,cloud,wordpress',
            'stack'    => 'required|in:any,php,javascript',
            'priority' => 'required|in:budget,features,control',
        ]);

        $answers = array_merge($validator, [
            'size'     => $validator['size'] ?? '',
            'comfort'  => $validator['comfort'] ?? '',
            'hosting'  => $validator['hosting'] ?? '',
            'stack'    => $validator['stack'] ?? '',
            'priority' => $validator['priority'] ?? '',
        ]);

        $result = ProductRecommender::recommend($answers);
        $candidates = ProductRecommender::candidates();

        // Keep the blank slate the visitor already filled in.
        $this->view('site.choose-result', [
            'cmsPage'   => $this->cmsPage('/choose'),
            'result'    => $result,
            'candidates'=> $candidates,
            'answer'    => $answers,
        ]);
    }

    private function cmsPage(string $path): ?array
    {
        $row = Page::forPath($path);

        return $row !== null ? Page::localized($row, I18n::current()) : null;
    }
}