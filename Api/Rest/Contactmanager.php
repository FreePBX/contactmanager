<?php
namespace FreePBX\modules\Contactmanager\Api\Rest;
use FreePBX\modules\Api\Rest\Base;
class Contactmanager extends Base {
	protected $module = 'contactmanager';
	public function setupRoutes($app) {

		/**
		 * @verb GET
		 * @returns - contactmanager groups
		 * @uri /contactmanager/groups
		 */
		$app->get('/groups', function ($request, $response, $args) {
			$groups = $this->freepbx->Contactmanager->getGroups();
			return $response->withJson($groups);
		})->add($this->checkAllReadScopeMiddleware());

		/**
		 * @verb GET
		 * @returns - contactmanager groups
		 * @uri /contactmanager/groups/:id
		 */
		$app->get('/groups/{id}', function ($request, $response, $args) {
			$groups = $this->freepbx->Contactmanager->getGroupsbyOwner($args['id']);
			return $response->withJson($groups);
		})->add($this->checkAllReadScopeMiddleware());

		/**
		 * @verb GET
		 * @returns - contactmanager group info
		 * @uri /contactmanager/groups/:id/:groupid
		 */
		$app->get('/groups/{id}/{groupid}', function ($request, $response, $args) {
			$group = $this->freepbx->Contactmanager->getGroupByID($args['groupid']);
			if($group['owner'] !== -1 && $group['owner'] !== $args['id']) {
				return $response->withJson(false);
			}
			return $response->withJson($group);
		})->add($this->checkAllReadScopeMiddleware());

		/**
		 * @verb GET
		 * @returns - contactmanager entry info
		 * @uri /contactmanager/groups/:id/:groupid/:entryid
		 */
		$app->get('/groups/{id}/{groupid}/entries', function ($request, $response, $args) {
			$group = $this->freepbx->Contactmanager->getGroupByID($args['groupid']);
			if($group['owner'] !== -1 && $group['owner'] !== $args['id']) {
				return $response->withJson(false);
			}
			$list = $this->freepbx->Contactmanager->getEntriesByGroupID($args['groupid']);
			return $response->withJson($list);
		})->add($this->checkAllReadScopeMiddleware());

		/**
		 * @verb GET
		 * @returns - contactmanager entries
		 * @uri /contactmanager/entries/:id
		 */
		$app->get('/entries/{id}', function ($request, $response, $args) {
			$entry = $this->freepbx->Contactmanager->getEntryByID($args['id']);
			return $response->withJson($entry);
		})->add($this->checkAllReadScopeMiddleware());
	}
}
