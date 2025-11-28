<?php

namespace ServiceChargeManager\Includes;

class Installer
{
    /**
     * Run the installer
     *
     * @return void
     */
    public function run()
    {
        ServiceChargeManagerActivator::activate();
        $this->add_version();
    }

    /**
     * Add version in DB
     *
     * @return void
     */
    public function add_version()
    {
        $installed = get_option('scm_installed_at');

        if (!$installed) {
            update_option('scm_installed_at', time());
        }

        update_option('scm_version', SCM_VERSION);
    }
}
