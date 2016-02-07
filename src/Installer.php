<?php namespace Comodojo\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Comodojo\Exception\InstallerException;
use Comodojo\Installer\Properties\Parser;
use Comodojo\Installer\Registry\SupportedTypes;
use Comodojo\Configuration\Installer as PackageInstaller;


/**
 *
 *
 * @package     Comodojo Framework
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @author      Marco Castiello <marco.castiello@gmail.com>
 * @license     GPL-3.0+
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class Installer extends LibraryInstaller {

    protected $package_installer;

    public function __construct(IOInterface $io, Composer $composer, PackageInstaller $package_installer = null) {

        $this->package_installer = $package_installer;

        parent::__construct($io, $composer);

    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType) {

        return in_array($packageType, SupportedTypes::getTypes());

    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {

        parent::install($repo, $package);

        if ( is_null($this->package_installer ) {

            $this->io->write('<error>PackageInstaller not ready or missing configuration: package could not be installed.</error>');

        } else {

            $this->packageInstall($package);

        }

    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target) {

        parent::update($repo, $initial, $target);

        if ( is_null($this->package_installer ) {

            $this->io->write('<error>PackageInstaller not ready or missing configuration: package could not be installed.</error>');

        } else {

            $this->packageUpdate($initial, $target);

        }

    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package) {



        if ( is_null($this->package_installer ) {

            $this->io->write('<error>PackageInstaller not ready or missing configuration: package could not be installed.</error>');

        } else {

            $this->packageUninstall($package);

        }

        parent::uninstall($repo, $package);

    }

    private function packageInstall($package) {

        $actions_map = Parser::parse($package);

        $package_name = $package->getPrettyName();

        $package_path = $this->composer->getInstallationManager()->getInstallPath($package);

        $installer = $this->getPackageInstaller();

        foreach ($actions_map as $action_class => $extra) {

            $action_fqcn = 'Comodojo\\Installer\\Actions\\' . $action_class;

            $action = new $action_fqcn($this->composer, $this->io, $package_path, $installer);

            $action->install($package_name, $extra);

        }

    }

    private function packageUninstall($package) {

        $actions_map = Parser::parse($package);

        $package_name = $package->getPrettyName();

        $package_path = $this->composer->getInstallationManager()->getInstallPath($package);

        $installer = $this->getPackageInstaller();

        foreach ($actions_map as $action_class => $extra) {

            $action_fqcn = 'Comodojo\\Installer\\Actions\\' . $action_class;

            $action = new $action_fqcn($this->composer, $this->io, $package_path, $installer);

            $action->uninstall($package_name, $extra);

        }

    }

    private function packageUpdate($initial, $target) {

        $initial_actions_map = Parser::parse($initial);

        $target_actions_map = Parser::parse($target);

        $initial_package_name = $initial->getPrettyName();

        $target_package_name = $target->getPrettyName();

        $initial_package_path = $this->composer->getInstallationManager()->getInstallPath($initial);

        $target_package_path = $this->composer->getInstallationManager()->getInstallPath($target);

        $initial_actions = array_keys($initial_actions_map);

        $target_actions = array_keys($target_actions_map);

        $uninstall = array_diff($initial_actions, $target_actions);

        $install = array_diff($target_actions, $initial_actions);

        $update = array_intersect($initial_actions, $target_actions);

        $installer = $this->getPackageInstaller();

        foreach ($uninstall as $action_uninstall) {

            $action_fqcn = 'Comodojo\\Installer\\Actions\\' . $action_uninstall;

            $action = new $action_fqcn($this->composer, $this->io, $initial_package_path, $installer);

            $action->uninstall($initial_package_name, $initial_actions_map[$action_uninstall]);

        }

        foreach ($install as $action_install) {

            $action_fqcn = 'Comodojo\\Installer\\Actions\\' . $action_install;

            $action = new $action_fqcn($this->composer, $this->io, $target_package_path, $installer);

            $action->install($target_package_name, $target_actions_map[$action_install]);

        }

        foreach ($update as $action_update) {

            $action_fqcn = 'Comodojo\\Installer\\Actions\\' . $action_update;

            $action = new $action_fqcn($this->composer, $this->io, $target_package_path, $installer);

            $action->update($target_package_name, $initial_actions_map[$action_update], $target_actions_map[$action_update]);

        }

    }

    private function getPackageInstaller() {

        return $this->package_installer;

    }

}
