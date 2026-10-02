<?php
/**
 * Base Middleware
 */

namespace App\Core;

abstract class Middleware
{
    protected Session $session;
    protected Request $request;
    protected Response $response;

    abstract public function handle(): void;

    public function __construct()
    {
        $this->session = Session::getInstance();
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Stop execution and return error
     */
    protected function reject(int $code = 403, string $message = 'Forbidden'): void
    {
        http_response_code($code);
        
        if ($this->request->isAjax()) {
            $this->response->json(['error' => $message], $code);
        }

        $view = new View();
        $user = $this->session->get('user');
        $view->render('layouts/error', [
            'code'    => $code,
            'message' => $message,
            'title'   => "Error {$code}",
            'user'    => $user,
        ]);
        exit;
    }
}
