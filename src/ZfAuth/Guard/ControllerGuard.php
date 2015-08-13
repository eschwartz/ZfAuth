<?php


namespace Aeris\ZfAuth\Guard;



use Aeris\ZfAuth\IdentityProvider\IdentityProviderInterface;
use Zend\Mvc\Router\RouteMatch;

class ControllerGuard implements GuardInterface {

	/** @var IdentityProviderInterface */
	protected $identityProvider;

	/** @var array[][] Allowed roles indexed by controller and action */
	protected $rules = [];

	public function __construct(array $rules = []) {
		$this->setRules($rules);
	}

	public function setRules(array $rules) {
		$this->rules = [];

		foreach ($rules as $rule) {
			$controller = strtolower($rule['controller']);
			$actions = isset($rule['actions']) ? (array)$rule['actions'] : [];
			$roles = (array)$rule['roles'];

			if (empty($actions)) {
				$this->rules[$controller][0] = $roles;
				continue;
			}

			foreach ($actions as $action) {
				$this->rules[$controller][strtolower($action)] = $roles;
			}
		}
	}

	/** @return boolean */
	public function isGranted(RouteMatch $routeMatch) {
		$allowedRoles = $this->getAllowedRoles($routeMatch);

		if (!$allowedRoles) {
			return false;
		}

		$matchingRoles = array_intersect($allowedRoles, $this->identityProvider->getIdentity()->getRoles());
		if (count($matchingRoles)) {
			return true;
		}

		return false;
	}

	protected function getAllowedRoles(RouteMatch $routeMatch) {
		$controller = strtolower($routeMatch->getParam('controller'));
		$action = strtolower($routeMatch->getParam('action'));
		$restAction = strtolower($routeMatch->getParam('restAction'));

		return @$this->rules[$controller][$action] ?: @$this->rules[$controller][$restAction];
	}

	/**
	 * @param IdentityProviderInterface $identityProvider
	 */
	public function setIdentityProvider(IdentityProviderInterface $identityProvider) {
		$this->identityProvider = $identityProvider;
	}
}