<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;
use App\Services\CsrfService;

/**
 * Base Controller.
 * 
 * Provides common helpers used by all controllers:
 *  - View rendering
 *  - JSON responses
 *  - Auth helpers
 *  - CSRF helpers
 *  - Validation
 *  - Service access
 */
abstract class Controller
{
    public function __construct(
        protected Request $request,
        protected Response $response,
        protected Database $db
    ) {}

    /**
     * Render a view with data.
     */
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        $this->response->setStatusCode($status);
        $this->response->view($template, $data, $status);
    }

    /**
     * Render a view with the default layout.
     */
    protected function render(string $template, array $data = [], string $layout = 'layouts.app'): void
    {
        $app = Application::getInstance();
        $user = $app->getService('auth')?->user();
        $data = array_merge([
            'app'   => $app,
            'user'  => $user,
            'csrf'  => $app->getService('csrf')?->token() ?? '',
            'flash' => $app->getService('session')?->getFlashes() ?? [],
            'config'=> $app->getConfig('app', []),
        ], $data);
        $this->response->view($template, $data);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): void
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function fail(string $message, array $errors = [], int $status = 400): void
    {
        $this->response->badRequest($message, $errors);
    }

    /**
     * Validate the request against rules.
     */
    protected function validate(array $rules, ?array $data = null): array
    {
        $data = $data ?? $this->request->all();
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $fieldErrors = [];

            foreach ($rules as $rule) {
                $args = [];
                if (str_contains($rule, ':')) {
                    [$rule, $argString] = explode(':', $rule, 2);
                    $args = explode(',', $argString);
                }
                $validator = "\\App\\Helpers\\Validators\\" . ucfirst($rule) . 'Validator';
                if (class_exists($validator)) {
                    $instance = new $validator();
                    $error = $instance->check($value, $args, $data);
                    if ($error) $fieldErrors[] = $error;
                } else {
                    $error = $this->applyRule($rule, $value, $args, $field, $data);
                    if ($error) $fieldErrors[] = $error;
                }
            }
            if ($fieldErrors) $errors[$field] = $fieldErrors;
            $validated[$field] = $value;
        }
        if ($errors) {
            $this->response->badRequest('Validation failed', $errors);
            exit;
        }
        return $validated;
    }

    private function applyRule(string $rule, mixed $value, array $args, string $field, array $data): ?string
    {
        return match ($rule) {
            'required'  => ($value === null || $value === '') ? "$field is required" : null,
            'string'    => ($value !== null && !is_string($value)) ? "$field must be a string" : null,
            'numeric'   => ($value !== null && !is_numeric($value)) ? "$field must be numeric" : null,
            'integer'   => ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) ? "$field must be an integer" : null,
            'email'     => ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) ? "$field must be a valid email" : null,
            'min'       => ($value !== null && mb_strlen((string)$value) < (int)$args[0]) ? "$field must be at least {$args[0]} characters" : null,
            'max'       => ($value !== null && mb_strlen((string)$value) > (int)$args[0]) ? "$field must not exceed {$args[0]} characters" : null,
            'between'   => ($value !== null && (mb_strlen((string)$value) < (int)$args[0] || mb_strlen((string)$value) > (int)$args[1])) ? "$field must be between {$args[0]} and {$args[1]} characters" : null,
            'in'        => ($value !== null && !in_array($value, $args, true)) ? "$field must be one of: " . implode(', ', $args) : null,
            'same'      => ($value !== ($data[$args[0]] ?? null)) ? "$field must match {$args[0]}" : null,
            'unique'    => $this->checkUnique($args, $field, $value) ? "$field already exists" : null,
            'exists'    => !$this->checkExists($args, $value) ? "$field does not exist" : null,
            'regex'     => ($value !== null && !preg_match('/' . $args[0] . '/', (string)$value)) ? "$field format is invalid" : null,
            default     => null,
        };
    }

    private function checkUnique(array $args, string $field, mixed $value): bool
    {
        if (!isset($args[0]) || $value === null) return false;
        [$table, $column] = [$args[0], $args[1] ?? $field];
        return (bool) $this->db->fetchValue(
            "SELECT COUNT(*) FROM {$this->db->quoteIdent($table)} WHERE {$this->db->quoteIdent($column)} = :v LIMIT 1",
            ['v' => $value]
        );
    }

    private function checkExists(array $args, mixed $value): bool
    {
        if (!isset($args[0]) || $value === null) return false;
        [$table, $column] = [$args[0], $args[1] ?? 'id'];
        return (bool) $this->db->fetchValue(
            "SELECT COUNT(*) FROM {$this->db->quoteIdent($table)} WHERE {$this->db->quoteIdent($column)} = :v LIMIT 1",
            ['v' => $value]
        );
    }

    protected function redirect(string $url, int $status = 302): void
    {
        $this->response->redirect($url, $status);
    }

    protected function back(): void
    {
        $url = $this->request->header('referer', '/');
        $this->response->redirect((string) $url);
    }

    protected function withFlash(string $type, string $message): void
    {
        Application::getInstance()->getService('session')?->flash($type, $message);
    }

    protected function user(): ?array
    {
        return Application::getInstance()->getService('auth')?->user();
    }

    protected function userId(): ?int
    {
        $u = $this->user();
        return $u ? (int) $u['id'] : null;
    }
}
