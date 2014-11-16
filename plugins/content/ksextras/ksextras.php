<?php
/**
 * @copyright Copyright 2014 Katalyst Solutions, LLC. All Rights Reserved.
 * @license Commercial
 */

defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');

/**
 * This is a custom testimonial plugin class to add additional fields to com_content to allow it to be used for reviews/testimonials 
 * and output the data using schema.org/Review. It will add an additional tab called Testimonial
 */
class plgContentKSExtras extends JPlugin
{
	/** var string Name of the plugin */
	protected $plg_name;
	
	/** var array List of fields to look for in the $attribs */
	protected $ksfields;
	
	/** var string The organization name */
	protected $organization;
	
	/** var string Organization Type used for Schema.org */
	protected $organization_type;
	
	/** var string Category */
	protected $category;
	
	/** var boolean Limit plugin to selected category */
	protected $limit_to_category;
	
	/** var boolean Include child categories */
	protected $include_child_categories;
	
	public function __construct(& $subject, $config)
	{
		// We only want to use this with com_content
		$jinput = JFactory::getApplication()->input;
		$option = $jinput->get('option');
		if ($option <> 'com_content')
		{
			return true;
		}
		
		parent::__construct($subject, $config);
		
		$this->plg_name					= 'ksextras';
		$this->ksfields					= $this->setKSFields();
		$this->organization				= $this->params->get('organization');
		$this->organization_type		= $this->params->get('organization_type');
		$this->category					= $this->params->get('category');
		$this->limit_to_category		= $this->params->get('limit_to_category');
		$this->include_child_categories	= $this->params->get('include_child_categories');

		// Load the language file for the plugin
		$this->loadLanguage();
	}
	
	/**
	 * Set the values for the ksfields array
	 * These should correspond to the fields in extras/testimonial.xml
	 * These should not use the same name as any com_content attribs fields
	 *
	 * @return array
	 */
	protected function setKSFields()
	{
		$ksfields = array (
			'testimonial_by',
			'author_job_title',
			'author_affiliation',
			'rating',
			'author_address',
			'author_locality',
			'author_region',
			'author_postal_code',
		);
		
		return $ksfields;
	}
	
	/**
	 * Prepare the form to add to the article edit
	 *
	 * @param object $form
	 * @param object $data
	 *
	 * @return bool
	 */
	public function onContentPrepareForm($form, $data)
	{
		// If the category id is set, then check if the plugin should be limited to a specific category
		if (!empty($data->catid))
		{
			$this->getChildCategories($data->catid);
			if ($this->limit_to_category && !$this->checkCategory($data->catid))
			{
				return true;
			}
		}
		
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		
		// Check that we are manipulating a valid form
		$name = $form->getName();
		
		if (!in_array($name, array('com_content.article')))
		{
			return true;
		}

		// Add the extra fields to the form
		JForm::addFormPath(dirname(__FILE__) . '/extras');
		$form->loadFile('testimonial', false);

		// Load the data from table into the form
		$articleId = isset($data->id) ? $data->id : 0;
		
		if ($articleId)
		{
			// Load the data from the database
			$db = JFactory::getDbo();

			$query='SELECT article_id, data FROM #__content_ksextras' .' WHERE article_id = '.(int) $articleId;
			$db->setQuery($query);
		
			$attribs = $db->loadObject();

			// Check for a database error.
			if ($db->getErrorNum())
			{
				$this->_subject->setError($db->getErrorMsg());
				return false;
			}

			// json_decode the data
			if (!empty($attribs->data))
			{
				$ksdata = json_decode(json_decode($attribs->data));
			}
		}
		
		// fill in the form with data
		if(isset($attribs))
		{
			foreach ($this->ksfields as $ksfield)
			{
				$data->attribs[$ksfield] = isset($ksdata->$ksfield) ? $ksdata->$ksfield : '';
			}
		}

		return true;
	}
	
	/**
	 * Runs on content preparation
	 *
	 * @param	string	$context	The context for the data
	 * @param	object	$data		An object containing the data for the form.
	 *
	 * @return boolean
	 * @TODO FINISH THIS METHOD
	 */
	public function onContentPrepareData($context, $data)
	{
		if (is_object($data))
		{
			$articleId = isset($data->id) ? $data->id : 0;
			
			if ($articleId > 0)
			{
				// Load the data from the database
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('data');
				$query->from('#__content_ksextras');
				$query->where('article_id = '.$db->Quote($articleId));
				$db->setQuery($query);
				$results = $db->loadObject();

				// Check for a database error
				if ($db->getErrorNum())
				{
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}
				
				$ksdata = (count($results)) ? json_decode(json_decode($results->data)) : new stdClass;

				// Merge the data
				$data->attribs = array();

				foreach ($this->ksfields as $ksfield)
				{
					$data->attribs[$ksfield] = isset($ksdata->$ksfield) ? $ksdata->$ksfield : '';
				}
			}
			else
			{
				// Load the form
				JForm::addFormPath(dirname(__FILE__).'/extras');
				$form = new JForm('com_content.article');
				$form->loadFile('testimonial', false);
// Need to test this piece
				// Merge the default values
				$data->attribs = array();
				foreach ($form->getFieldset('attribs') as $field)
				{
					$data->attribs[] = array($field->fieldname, $field->value);
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Fires after content save post event hook to save custom data into #__content_ksextras
	 *
	 * @param $context
	 * @param $data
	 * @param $isNew
	 *
	 * @return bool
	 */
	public function onContentAfterSave($context, $data, $isNew)
	{
		// Check if we are manipulating a valid form
		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}
		
		// Get the article id or set to 0 if new article
		// ????????? Should it not have an article id after it has saved??????
		$articleId = isset($data->id) ? $data->id : 0;

		// Get the attributes
		$attribs = json_decode($data->attribs);
		
		// Pull out the extra fields to insert into the table
		$ksattribs = array();
		foreach ($this->ksfields as $ksfield)
		{
			$ksattribs[$ksfield] = isset($attribs->$ksfield) ? $attribs->$ksfield : '';
		}
		$ksattribs = json_encode($ksattribs);

		// Get the database object
		$db = JFactory::getDbo();
		
		// Check for an existing entry
		$db->setQuery('SELECT COUNT(*) FROM #__content_ksextras WHERE article_id = '.$articleId);
		$res = $db->loadResult();
		
		// Updating or adding
		if (!empty($res)) // updating record
		{
			$this->updateRecord($ksattribs, $articleId);
		}
		else // Adding a new record
		{
			$this->insertRecord($ksattribs, $articleId);
		}
		
/*		
		if ($articleId && isset($article->testimonial) && count($article->testimonial))
		{
			try
			{
				$db = JFactory::getDbo();
				
				$query = $db->getQuery(true);
				$query->delete('#__content_ksextras');
				$query->where('article_id = '.$db->Quote($articleId));
				$query->where('data LIKE '.$db->Quote('testimonial.%'));
				$db->setQuery($query);
				if (!$db->query())
				{
					throw new Exception($db->getErrorMsg());
				}
				
				$query->clear();
				$query->insert('#__content_ksextras');
				$order = 1;/////////// ???????????????????????????????????????????
				foreach ($article->testimonial as $k => $v)
				{/////////// ???????????????????????????????????????????
					$query->values($articleId.', '.$db->Quote('testimonial.'.$k).', '.$db->Quote(json_encode($v)).', '.$order++);
				}
				$db->setQuery($query);
				
				if (!$db->query)
				{
					throw new Exception($db->getErrorMsg());
				}
			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}
		
		return true;
*/
	}
	
	/**
	* Remove the data when the article is deleted
	*
	* Method is called before article data is deleted from the database
	*
	* @param string The context for the content passed to the plugin.
	* @param object The data relating to the content that was deleted.
	*
	* @return bool
	* @throws JException
	*/
	public function onContentAfterDelete($context, $data)
	{
		 // get the article id
		$articleId = isset($data->id) ? $data->id : 0;
		
		if ($articleId)
		{
			try
			{
				$db = JFactory::getDbo();
				
				$db->setQuery('DELETE FROM #__content_kstestominials WHERE article_id = '.$articleId );
				
				if (!$db->execute()) {
					throw new Exception($db->getErrorMsg());
				}
			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				
				return false;
			}
		}

		return true;
/*		
		$articleId = $article->id;
		if ($articleId)
		{
			try
			{
				$db = JFactory::getDbo();
				
				$query = $db->getQuery(true);
				$query->delete();
				$query->from('#__content_ksextras');
				$query->where('article_id = '.$db->Quote($articleId));
				$query->where('data LIKE '.$db->Quote('testimonial.%'));
				$db->setQuery($query);
				
				if (!$db->query())
				{
					throw new Exception ($db->getErrorMsg());
				}
			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}
		
		return true;
*/
	}
	
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (!isset($article->testimonial) || !count($article->testimonial))
		         return;

		      // add extra css for table
		      $doc = JFactory::getDocument();
//		      $doc->addStyleSheet(JURI::base(true).'/plugins/content/ksextras/extras/extras.css');

		      // construct a result table on the fly   
		      jimport('joomla.html.grid');
		      $table = new JGrid();

		      // Create columns
		      $table->addColumn('attr')->addColumn('value');   

		      // populate
		      $rownr = 0;
		      foreach ($article->testimonial as $attr => $value) {
		         $table->addRow(array('class' => 'row'.($rownr % 2)));
		         $table->setRowCell('attr', $attr);
		         $table->setRowCell('value', $value);
		         $rownr++;
		      }

		      // wrap table in a classed <div>
		      $suffix = $this->params->get('testimonialclass_sfx', 'testimonial');
		      $html = '<div class="'.$suffix.'">'.(string)$table.'</div>';

		      $article->text = $html.$article->text;
	}

	 /**
	* Insert a new record into the database
	*
	* @param $attribs our extra fields in object form
	* @param $articleId the article id we are relating the fields to
	*
	* @return bool
	* @throws Exception
	*/
	public function insertRecord($attribs, $articleId)
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array('article_id', 'data', 'created', 'created_by');

		$user = JFactory::getUser();
		$created_by = $user->id;
		$created = JFactory::getDate()->toSql();

		// Insert values.
		$values = array(
			$articleId,
			$db->quote(json_encode($attribs)),
			$db->quote($created),
			$created_by,
		);

		// insert query
		$query
			->insert($db->quoteName('#__content_ksextras'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		// set the query
		$db->setQuery($query);

		// execute, throw an exception if we have a problem
		if (!$db->execute()) {
			throw new Exception($db->getErrorMsg());
		}

		return true;
	}


	/**
	* Update record function
	 *
	* @param $attribs requires object of attributes from form
	* @param $articleId id of the article we are relating to
	*
	* @return bool
	* @throws Exception
	*/
	protected function updateRecord($attribs, $articleId)
	{
		$db = JFactory::getDbo();
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		$conditions = array(
			'article_id='.$articleId,
		);

		$user = JFactory::getUser();
		$modified_by = $user->id;
		$modified = JFactory::getDate()->toSql();

		// Fields to update.
		$fields = array(
			'data='.$db->quote(json_encode($attribs)),
			'modified='.$db->quote($modified),
			'modified_by='.$modified_by,
		);

		// update query
		$query->update($db->quoteName('#__content_ksextras'))->set($fields)->where($conditions);

		// set the query
		$db->setQuery($query);

		// execute, throw an exception if we have a problem
		if (!$db->execute()) {
			throw new Exception($db->getErrorMsg());
		}

		return true;
	}
	
	/**
	 * Check the article category against the category selected by the plugin
	 *
	 * @param integer	$article_cat		The category of the article
	 * 
	 * @return boolean
	 */
	protected function checkCategory($article_cat)
	{
		if ($this->category == $article_cat)
		{
			return true;
		}
		
		if ($this->include_child_categories)
		{
			$children = $this->getChildCategories($article_cat);
			if (in_array($article_cat, $children))
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get a list of the category children
	 *
	 * @param integer	$catId	The id of the category to check
	 *
	 * @return array
	 */
	protected function getChildCategories($catId)
	{
		jimport('joomla.application.categories');
		$categories = JCategories::getInstance('Content');
		$cat		= $categories->get($catId);
		$children	= $cat->getChildren(false);
		$childCats	= array();
		
		foreach ($children as $child)
		{
			$childCats[] = $child->id;
		}

		return $childCats;
	}
}