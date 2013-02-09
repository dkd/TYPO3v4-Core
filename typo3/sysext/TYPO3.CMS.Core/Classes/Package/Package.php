<?php
namespace TYPO3\CMS\Core\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A Package
 *
 * @api
 */
class Package extends \TYPO3\Flow\Package\Package implements PackageInterface {

	const PATTERN_MATCH_EXTENSIONKEY = '/^[0-9a-z_]+$/i';

	/**
	 * @var array
	 */
	protected $extensionManagerConfiguration = array();

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Package\PackageManager $packageManager the package manager which knows this package
	 * @param string $packageKey Key of this package
	 * @param string $packagePath Absolute path to the location of the package's composer manifest
	 * @param string $classesPath Path the classes of the package are in, relative to $packagePath. Optional, read from Composer manifest if not set.
	 * @param string $manifestPath Path the composer manifest of the package, relative to $packagePath. Optional, defaults to ''.
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageKeyException if an invalid package key was passed
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackagePathException if an invalid package path was passed
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageManifestException if no composer manifest file could be found
	 */
	public function __construct(\TYPO3\Flow\Package\PackageManager $packageManager, $packageKey, $packagePath, $classesPath = NULL, $manifestPath = '') {
		if (preg_match(self::PATTERN_MATCH_EXTENSIONKEY, $packageKey) === 1) {
			if (!(is_dir($packagePath) || (\TYPO3\Flow\Utility\Files::is_link($packagePath) && is_dir(\TYPO3\Flow\Utility\Files::getNormalizedPath($packagePath))))) {
				throw new \TYPO3\Flow\Package\Exception\InvalidPackagePathException(sprintf('Tried to instantiate a package object for package "%s" with a non-existing package path "%s". Either the package does not exist anymore, or the code creating this object contains an error.', $packageKey, $packagePath), 1166631889);
			}
			if (substr($packagePath, -1, 1) !== '/') {
				throw new \TYPO3\Flow\Package\Exception\InvalidPackagePathException(sprintf('The package path "%s" provided for package "%s" has no trailing forward slash.', $packagePath, $packageKey), 1166633720);
			}
			if (substr($classesPath, 1, 1) === '/') {
				throw new \TYPO3\Flow\Package\Exception\InvalidPackagePathException(sprintf('The package classes path provided for package "%s" has a leading forward slash.', $packageKey), 1334841320);
			}
			if (!file_exists($packagePath . $manifestPath . 'ext_emconf.php')) {
				throw new \TYPO3\Flow\Package\Exception\InvalidPackageManifestException(sprintf('No ext_emconf file found for package "%s". Please create one at "%sext_emconf.php".', $packageKey, $manifestPath), 1360403545);
			}
			$this->packageManager = $packageManager;
			$this->manifestPath = $manifestPath;
			$this->packageKey = $packageKey;
			$this->packagePath = \TYPO3\Flow\Utility\Files::getNormalizedPath($packagePath);
			$this->classesPath = self::DIRECTORY_CLASSES;
			$this->getExtensionEmconf($packageKey, $this->packagePath);
		} else {
			parent::__construct($packageManager, $packageKey, $packagePath, $classesPath, $manifestPath);
		}
	}

	/**
	 * @param string $extensionKey
	 * @param string $extensionPath
	 * @return bool
	 */
	protected function getExtensionEmconf($extensionKey, $extensionPath) {
		$_EXTKEY = $extensionKey;
		$path = $extensionPath . '/ext_emconf.php';
		$EM_CONF = NULL;
		if (file_exists($path)) {
			include $path;
			if (is_array($EM_CONF[$_EXTKEY])) {
				$this->extensionManagerConfiguration = $EM_CONF[$_EXTKEY];
				$this->mapExtensionManagerConfigurationToComposerManifest();
			}
		}
		return FALSE;
	}

	/**
	 *
	 */
	protected function mapExtensionManagerConfigurationToComposerManifest() {
		if (is_array($this->extensionManagerConfiguration)) {
			$extensionManagerConfiguration = $this->extensionManagerConfiguration;
			$composerManifest = $this->composerManifest = new \stdClass();
			$composerManifest->name = $this->getPackageKey();
			$composerManifest->type = 'typo3-cms-extension';
			$composerManifest->description = $extensionManagerConfiguration['title'];
			$composerManifest->version = $extensionManagerConfiguration['version'];
			if (isset($extensionManagerConfiguration['constraints']['depends']) && is_array($extensionManagerConfiguration['constraints']['depends'])) {
				$composerManifest->require = new \stdClass();
				foreach ($extensionManagerConfiguration['constraints']['depends'] as $requiredPackageKey => $requiredPackageVersion) {
					$composerManifest->require->$requiredPackageKey = $requiredPackageVersion;
				}
			}
			if (isset($extensionManagerConfiguration['constraints']['conflicts']) && is_array($extensionManagerConfiguration['constraints']['conflicts'])) {
				$composerManifest->conflict = new \stdClass();
				foreach ($extensionManagerConfiguration['constraints']['conflicts'] as $conflictingPackageKey => $conflictingPackageVersion) {
					$composerManifest->conflict->$conflictingPackageKey = $conflictingPackageVersion;
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getPackageReplacementKeys() {
		return $this->getComposerManifest('replace') ?: array();
	}
}

?>