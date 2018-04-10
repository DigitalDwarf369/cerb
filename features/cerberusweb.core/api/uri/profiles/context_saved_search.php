<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesContextSavedSearch extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$context = CerberusContexts::CONTEXT_SAVED_SEARCH;
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // saved_search 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($context_saved_search = DAO_ContextSavedSearch::get($id))) {
			return;
		}
		$tpl->assign('context_saved_search', $context_saved_search);
		
		// Tab persistence
		
		$point = 'profiles.context_saved_search.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		if(!empty($context_saved_search->owner_context)) {
			$properties['owner'] = array(
				'label' => DevblocksPlatform::translateCapitalized('common.owner'),
				'type' => Model_CustomField::TYPE_LINK,
				'value' => $context_saved_search->owner_context_id,
				'params' => [
					'context' => $context_saved_search->owner_context,
				]
			);
		}
		
		$properties['tag'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.tag'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $context_saved_search->tag,
		);
		
		
		$context_ext = $context_saved_search->getContextExtension(false);
		$properties['context'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.context'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $context_ext->name,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $context_saved_search->updated_at,
		);
		
		$properties['query'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.query'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $context_saved_search->query,
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $context_saved_search->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $context_saved_search->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$context_saved_search->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$context_saved_search->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, 'cerberusweb.contexts.context.saved.search');
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/context_saved_search.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_ContextSavedSearch::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
				@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
				@$query = DevblocksPlatform::importGPC($_REQUEST['query'], 'string', '');
				@$tag = DevblocksPlatform::importGPC($_REQUEST['tag'], 'string', '');
				
				@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
				
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_BOT:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}
				
				$tag = DevblocksPlatform::strAlphaNum($tag, '-');
					
				if(empty($id)) { // New
					$fields = array(
						DAO_ContextSavedSearch::CONTEXT => $context,
						DAO_ContextSavedSearch::OWNER_CONTEXT => $owner_context,
						DAO_ContextSavedSearch::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_ContextSavedSearch::NAME => $name,
						DAO_ContextSavedSearch::QUERY => $query,
						DAO_ContextSavedSearch::TAG => $tag,
						DAO_ContextSavedSearch::UPDATED_AT => time(),
					);
					
					// Validate fields from DAO
					if(!DAO_ContextSavedSearch::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ContextSavedSearch::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ContextSavedSearch::create($fields);
					DAO_ContextSavedSearch::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SAVED_SEARCH, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ContextSavedSearch::CONTEXT => $context,
						DAO_ContextSavedSearch::OWNER_CONTEXT => $owner_context,
						DAO_ContextSavedSearch::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_ContextSavedSearch::NAME => $name,
						DAO_ContextSavedSearch::QUERY => $query,
						DAO_ContextSavedSearch::TAG => $tag,
						DAO_ContextSavedSearch::UPDATED_AT => time(),
					);
					
					// Validate fields from DAO
					if(!DAO_ContextSavedSearch::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ContextSavedSearch::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ContextSavedSearch::update($id, $fields);
					DAO_ContextSavedSearch::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SAVED_SEARCH, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=saved_search', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.context.saved.search.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=saved_search&id=%d-%s", $row[SearchFields_ContextSavedSearch::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ContextSavedSearch::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ContextSavedSearch::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
