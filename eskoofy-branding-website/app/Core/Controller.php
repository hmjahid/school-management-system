<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $template, array $data = []): void
    {
        extract($data);
        $content = View::resolve($template);
        $isAdmin = str_starts_with(static::class, 'App\\Controllers\\Admin\\');
        $layout = $data['layout'] ?? ($isAdmin ? 'layouts.admin' : 'layouts.main');
        $layoutPath = __DIR__ . '/../../views/' . str_replace(['.', '-'], ['/', '_'], $layout) . '.php';

        ob_start();
        if (file_exists($content)) {
            require $content;
        }
        $contentHtml = ob_get_clean();

        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $contentHtml;
        }
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function success(mixed $data = null, string $message = 'Success'): void
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function error(string $message = 'Error', int $status = 400, mixed $data = null): void
    {
        $this->json(['success' => false, 'message' => $message, 'data' => $data], $status);
    }

    protected function paginated(array $pagination, string $message = 'Success'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $pagination['data'],
            'meta'    => [
                'pagination' => [
                    'current_page' => $pagination['current_page'],
                    'per_page'     => $pagination['per_page'],
                    'total'        => $pagination['total'],
                    'last_page'    => $pagination['last_page'],
                ],
            ],
        ]);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function withSuccess(string $message): void
    {
        Session::getInstance()->flash('success', $message);
    }

    protected function withError(string $message): void
    {
        Session::getInstance()->flash('error', $message);
    }

    protected function validate(array $rules): array
    {
        $validator = new Validator($_POST, $rules);
        if ($validator->fails()) {
            Session::getInstance()->flash('errors', $validator->errors());
            $this->back();
            exit;
        }
        return $validator->validated();
    }
}
