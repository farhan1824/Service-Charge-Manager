<?php

namespace ServiceChargeManager\Public;

class WorkflowManager
{
    const WORKFLOW_KEY = 'scm_registration_workflow';
    const WORKFLOW_STEPS = [
        'sign' => 1,
        'registration' => 2,
        'dashboard' => 3
    ];

    public static function init()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public static function getCurrentStep()
    {
        return isset($_SESSION[self::WORKFLOW_KEY]) ? $_SESSION[self::WORKFLOW_KEY] : 'index.php/show-signup-or-login';
    }

    public static function setCurrentStep($step)
    {
        if (array_key_exists($step, self::WORKFLOW_STEPS)) {
            $_SESSION[self::WORKFLOW_KEY] = $step;
            return true;
        }
        return false;
    }

    public static function canAccessStep($requestedStep)
    {
        $currentStep = self::getCurrentStep();
        return self::WORKFLOW_STEPS[$requestedStep] <= self::WORKFLOW_STEPS[$currentStep];
    }

    public static function resetWorkflow()
    {
        unset($_SESSION[self::WORKFLOW_KEY]);
    }

    public static function enforceWorkflow($step)
    {
        // If user is logged in, they can access dashboard directly
        if (is_user_logged_in() && $step === 'dashboard') {
            return;
        }

        // Otherwise check workflow progression
        if (!self::canAccessStep($step)) {
            wp_redirect(home_url('/index.php/show-signup-or-login')); // Redirect to sign page
            exit;
        }
    }
}
