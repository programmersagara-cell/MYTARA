<?php
/**
 * Startup Controller - System initialization/entry sequence
 */

namespace App\Controllers;

use App\Core\Controller;

class StartupController extends Controller
{
    /**
     * Show system startup/initialization sequence
     */
    public function index(): void
    {
        // Resolve the redirect target here (controller context) so the view
        // never needs to touch the session directly.
        $redirectTarget = $this->session->get('startup_redirect_target') ?: '/dashboard';

        $this->render('startup/index', [
            'title' => 'Initializing ITSaAMS',
            'layout' => 'layouts/startup',
            'redirect_target' => $redirectTarget,
        ]);
    }
}