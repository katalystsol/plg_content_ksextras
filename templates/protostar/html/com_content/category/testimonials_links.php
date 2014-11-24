<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content layout override
 *
 * @copyright   Copyright (C) 2014 Katalyst Solutions, LLC. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>


<div class="items-more">
<ol class="nav nav-tabs nav-stacked">
<?php
	foreach ($this->link_items as &$item) :
?>
	<li>
		<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid)); ?>">
			<?php echo $item->title; ?></a>
	</li>
<?php endforeach; ?>
</ol>
</div>
